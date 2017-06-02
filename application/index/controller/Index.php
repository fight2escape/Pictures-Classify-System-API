<?php
namespace app\index\controller;
use think\Controller;
use think\Validate;

class Index extends Controller
{
    public function index()
    {
        return 'Hello HuaWei!';
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
		// 数据校验
		$p = input('post.');
		$validate = new Validate([
			'session'	=>	'require',
			'password'	=>	'require|min:6'
		]);
		if(!$validate->check($p)) {
			return show(0, [], $validate->getError());
		}
		// 检查用户
		$user = db('user')->where('session',$p['session'])->find();
		if(!$user){
			return show(0,[],'用户不存在');
		}
		// 新密码入库
		$update = [
			'password'	=>	getPassword($p['password']),
			'update_time'	=>	time()
		];
		$res = db('user')->where('id',$user['id'])->update($update);
		if(!$res){
			return show(0,[],'密码修改失败');
		}else{
			return show(1,[],'密码修改成功');
		}
	}

	/**
	 * 用户修改头像
	 * 1、数据校验
	 * 2、上传图片
	 * 3、记录旧头像地址
	 * 4、新图入库
	 * 5、删除旧图
	 * 6、返回相对地址
	 * @return \think\response\Json
	 * @throws \think\Exception
	 */
	public function changeAvatar(){
		// 数据校验
		$p = input('post.');
		$validate = new Validate([
			'session'	=>	'require'
		]);
		if(!$validate->check($p)) {
			return show(0, [], $validate->getError());
		}
		// 检查用户
		$user = db('user')->where('session',$p['session'])->find();
		if(!$user){
			return show(0,[],'用户不存在');
		}
		// 接收头像信息
		$image = request()->file();
		if(!$image){
			return show(0,[],'未接收到图片文件');
		}
		foreach($image as $img){
			$info = $img->move(ROOT_PATH . 'public' . DS . 'uploads');
			if(!$info){
				return show(0,[],'图片上传失败:'.$info->getError());
			}else{
				// 先记录下原图地址
				$oldAvatar = $user['avatar'];
				// 新图替换旧图
				$avatar = $info->getSaveName();
				$update = [
					'avatar'	=>	$avatar,
					'update_time'	=>	time()
				];
				$res = db('user')->where('id',$user['id'])->update($update);
				if(!$res){
					return show(0,[],'数据入库失败');
				}else{
					// 修改成功后删掉原图片,如果旧头像路径为空则不操作
					if($oldAvatar!=''){
						unlink(FILE_PATH.$oldAvatar);
					}
					// 返回头像地址
					$data = [
						'avatar'	=>	$avatar
					];
					return show(1,$data,'头像修改成功');
				}
			}
		}
	}


	/**
	 * 修改个人信息
	 * @return \think\response\Json
	 * @throws \think\Exception
	 */
	public function editUserInfo()
	{
		// 数据校验
		$p = input('post.');
		$validate = new Validate([
			'session'	=>	'require',
			'nickname'	=>	'max:60',
			'gender'	=>	'in:1,2,3',
			'preference'=>	'in:1,2',
		]);
		if(!$validate->check($p)){
			return show(0,[],$validate->getError());
		}
		// 数据入库
		$update = [
			'nickname'	=>	$p['nickname'],
			'gender'	=>	$p['gender'],
			'preference'	=>	$p['preference'],
			'update_time'	=> time()
		];
		$res = db('user')->where('session',$p['session'])->update($update);
		if(!$res){
			return show(0,[],'修改失败');
		}else{
			return show(1,[],'修改成功');
		}
	}


	/**
	 * 用户登陆
	 * @return \think\response\Json
	 * @throws \think\Exception
	 */
    public function login()
    {
    	// 数据校验
    	$p = input('post.');
    	$validate = new Validate([
    		'username'	=>	'require|max:40',
    		'password'	=>	'require|min:6'
    	]);
    	if(!$validate->check($p)){
    		return show(0,[],$validate->getError());
    	}
		// 判断用户是否存在
    	$where = [];
    	$where['username']	=	$p['username'];
    	$where['password']	=	getPassword($p['password']);
    	$user = db('user')->where($where)->find();
    	if(empty($user)){
    		return show(0,[],'用户不存在');
    	}else{
			// 更新用户session
    		$session = encrypt($p['username'].$p['password']);
			$update = [
				'session'	=>	$session,
				'update_time'	=>	time()
			];
			$res = db('user')->where('id',$user['id'])->update($update);
			if(!$res){
				return show(0,[],'数据库更新失败');
			}else{
				// 入库后回信
				$data = [
	 				'session'	=>	$session,
	 				'nickname'	=>	$user['nickname'],
	 				'avatar'	=>	$user['avatar'],
	 				'gender'	=>	$user['gender'],
	 				'preference'=>	$user['preference']
	 			];
 				return show(1,$data,'登陆成功');
			}
    	}
    }


	/**
	 * 用户注册
	 * @return \think\response\Json
	 */
    public function register()
    {
    	// 数据校验
    	$p = input('post.');
    	$validate = new Validate([
    		'username'	=>	'require|max:40|unique:user',
    		'password'	=>	'require|min:6'
    	]);
    	if(!$validate->check($p)){
    		return show(0,[],$validate->getError());
    	} 
    	// 新用户信息初始化
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
    	$res = db('user')->insert($data);
    	// 入库后回信
 		if($res){
 			$data = [
 				'session'	=>	$session,
 				'nickname'	=>	$p['username'],
 				'gender'	=>	3,
 				'preference'=>	2
 			];
 			return show(1,$data,'注册成功');
 		}else{
 			return show(0,[],'用户添加失败');
 		}
    }

}
 