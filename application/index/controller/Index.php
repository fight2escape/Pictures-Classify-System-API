<?php
namespace app\index\controller;

use think\Controller;
use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Index extends Controller
{
    public function index()
    {
        return res(1,[],'Hello World!');
    }


    /**
     * 修改个人头像
     * 1、校验数据
     * 2、上传图片、缩放、压缩
     * 3、更新用户信息
     * 4、删除原头像
     * @return string
     */
    public function changeAvatar()
    {
//        1、校验数据
        $vld = MyValidate::makeValidate(['session']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        2、上传图片
        $image = request()->file();
        foreach($image as $img){
//            进行图像缩放，压缩
            $info = $img->move(config('AVATAR_PATH'));
            $save_path = getAvatarFullPath($info->getSaveName());
            $img = Img::open($save_path);
            $img->thumb(200,200)->save($save_path);
        }
        if(!$info){
            return res($image->getError());
        }
        $avatar_path = $info->getSaveName();
//        3、更新用户信息
//        先获取原来的头像路径，然后更新成功后再删除原头像
        $userInfo = db('user')->where('id',$uid)->find();
        $old_avatar = getAvatarFullPath($userInfo['avatar']);
        $update = [
            'avatar'	=>	$avatar_path,
            'update_time'	=>	time()
        ];
        $res = db('user')->where('id',$uid)->update($update);
        if(!$res){
            return res('用户头像更新失败');
        }
//        4、删除原头像
        if(is_file($old_avatar)){
            unlink($old_avatar);
        }
        return res('头像更新成功',1,$update);
    }



    /**
     * 用户修改密码
     * 数据校验
     * 新密码入库
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function changePassword()
    {
//        1、校验数据
        $vld = MyValidate::makeValidate(['session','password']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        2、执行更新
        $data = [
            'password'      =>  getPassword($p['password']),
            'update_time'   =>  time()
        ];
        $res = db('user')->where('id',$uid)->update($data);
        return $res?res('密码更新成功',1,[]):res('密码更新失败');
    }


    /**
	 * 修改个人信息
     * 1、校验session是否存在
     * 2、准备要更新的数据
     * 3、执行更新操作
	 * @return \think\response\Json
	 * @throws \think\Exception
	 */
	public function editUserInfo()
	{
        $vld = MyValidate::makeValidate(['session','nickname','gender','preference']);
        if($vld!== true){ return res($vld); }
        $p = input('post.');
//        1、校验session是否存在
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        2、准备要更新的数据
		$update = [
			'nickname'	=>	$p['nickname'],
			'gender'	=>	$p['gender'],
			'preference'	=>	$p['preference'],
			'update_time'	=> time()
		];
//        3、执行更新操作
		$res = db('user')->where('id',$uid)->update($update);
        return $res?res('更新成功',1,[]):res('更新失败');
	}


    /**
     * 用户登陆
     * 1、判断用户是否存在
     * 2、更新用户session
     * 3、session和用户id的映射,过期时间一小时
     * 4、返回所需数据
     * @return string
     */
    public function login()
    {
        $vld = MyValidate::makeValidate(['username','password']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');

//        1、判断用户是否存在
    	$where = [];
    	$where['username']	=	$p['username'];
    	$where['password']	=	getPassword($p['password']);
    	$userInfo = db('user')->where($where)->find();
    	if(empty($userInfo)){
    		return res('用户不存在');
    	}else{
//            2、更新用户session
    		$session = encrypt($p['username'].$p['password']);
			$update = [
				'session'	=>	$session,
				'update_time'	=>	time()
			];
			$res = db('user')->where('id',$userInfo['id'])->update($update);
//            3、session和用户id的映射,过期时间一小时
            $redis = new Redis();
//            先清除之前的session
            $redis->deleteSession($userInfo['session']);
            $session_res = $redis->setSession($session,$userInfo['id']);
			if(!$res || !$session_res){
				return res('数据库更新失败');
			}else{
//	             4、返回所需数据
				$data = [
	 				'session'	=>	$session,
	 				'nickname'	=>	$userInfo['nickname'],
	 				'avatar'	=>	$userInfo['avatar'],
	 				'gender'	=>	$userInfo['gender'],
	 				'preference'=>	$userInfo['preference']
	 			];
 				return res('登陆成功',1,$data);
			}
    	}
    }


    /**
     * 用户注册
     * 1、新用户信息初始化
     * 2、用户数据入库，获取用户唯一标识id
     * 3、入库后回信
     * @return string
     */
    public function register()
    {
        $vld = MyValidate::makeValidate(['username_unique','password']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');

//        1、新用户信息初始化
        $session = encrypt($p['username'].$p['password']);
        $data = [
            'session'	=>	$session,
            'username'	=>	$p['username'],
            'password'	=>	getPassword($p['password']),
            'nickname'	=>	$p['username'],
            'avatar'	=>	'',
            'create_time'=>	time(),
            'update_time'=>	time()
        ];
//       2、用户数据入库，获取用户唯一标识id
        $uid = db('user')->insertGetId($data);
//       session和用户id的映射,过期时间一小时
        $redis = new Redis();
        $session_res = $redis->setSession($session,$uid);
//       3、入库后回信
        if($uid && $session_res){
            $data = [
                'session'	=>	$session,
                'nickname'	=>	$p['username'],
                'gender'	=>	3,
                'preference'=>	2
            ];
            return res('注册成功',1,$data);
        }else{
            return res('注册失败',0,[$uid,$session_res]);
        }
    }
}