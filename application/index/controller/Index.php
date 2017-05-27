<?php
namespace app\index\controller;

use RedisLib\Redis;
use app\index\model\MyValidate;

class Index
{

    public function index()
    {
        return res(1,[],'Hello World!');
    }


    /**
     * 修改个人头像
     * 1、校验数据
     * 2、上传图片
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
            $info = $img->move(config('AVATAR_PATH'));
        }
        if(!$info){
            return res($image->getError());
        }
        $path = $info->getSaveName();
//        3、更新用户信息
//        先获取原来的头像路径，然后更新成功后再删除原头像
        $userInfo = $redis->getUserInfoByUid($uid);
        $old_avatar = $userInfo['avatar'];
        $update = [ 'avatar'=>$path ];
        $res = $redis->updateUserInfo($uid,$update);
        if(!$res){
            return res('用户头像更新失败');
        }
//        4、删除原头像
        unlink(getAvatarFullPath($old_avatar));
        return res('头像更新成功',1,$update);
    }

    /**
     * 修改个人密码
     * @return string
     */
    public function changePassword()
    {
//        1、校验数据
        $vld = MyValidate::makeValidate(['password']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        2、执行更新
        $data = ['password'=>getPassword($p['password'])];
        $res = $redis->updateUserInfo($uid,$data);
        return $res?res('密码更新成功',1,[]):res('密码更新失败');
    }

    /**
     * 修改个人信息
     * 1、校验数据
     * 2、准备要更新的数据
     * 3、执行更新操作
     * @return string
     */
    public function editUserInfo()
    {
//        1、校验数据
        $vld = MyValidate::makeValidate(['session','nickname','gender','preference']);
        if($vld!== true){ return res($vld); }
        $p = input('post.');
//        校验session是否存在
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        2、准备要更新的数据
        $update = [
            'nickname'  =>  $p['nickname'],
            'gender'    =>  $p['gender'],
            'preference'=>  $p['preference']
        ];
//        3、执行更新操作
        $res = $redis->updateUserInfo($uid,$update);
        return $res?res('更新成功',1,[]):res('更新失败');
    }

    /**
     * 用户登陆
     * 1、数据校验
     * 2、获取用户信息来校验用户名和密码
     * 3、删除旧session映射，生成并更新session
     * 4、更新成功，获取所需数据返回
     * 5、更新失败，回滚返回错误
     * @return string
     */
    public function login()
    {
//        1、校验数据
        $vld = MyValidate::makeValidate(['username','password']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
//        启动redis
        $redis = new Redis();
//        判断用户是否存在，存在则为uid，否则为报错提示
        $uid = MyValidate::checkUserExistsByUsername($redis,$p['username']);
        if(!is_numeric($uid)){ return res($uid); }

//        2、获取用户名和密码以及session
        $userInfo = $redis->getUserInfoByUid($uid,['username','password','session']);
        if(!$userInfo){
            return res('用户数据不存在，请重新登录');
        }
//        校验用户名和密码
        if($p['username']==$userInfo['username'] && getPassword($p['password'])==$userInfo['password']){

//            3、先清除之前的session
            $redis->deleteSession($userInfo['session']);
//            生成session并更新数据
            $u_session = getNewSession($p['username']);
            $d = ['session'=>$u_session];
            $res = $redis->updateUserInfo($uid,$d);
            $res1 = $redis->setSession($u_session,$uid);
//            成功则获取需要的数据，否则回滚
            if($res && $res1){
//                4、返回所需数据
                $keyArr = ['session','nickname','avatar','gender','preference'];
                $info = $redis->getUserInfoByUid($uid,$keyArr);
                return $info?res('登陆成功',1,$info):res('用户信息获取失败');
            }else{
//                5、回滚返回错误
                $redis->deleteSession($u_session);
                return res('系统繁忙，请稍后再试',0,[$res,$res1]);
            }
        }else{
            return res('用户名或密码错误',0,[$userInfo,]);
        }
    }


    /**
     * 用户注册
     * 1、校验数据
     * 2、数据准备
     * 3、用户数据入库
     * 4、处理结果
     * @return string
     */
    public function register()
    {
//        1、校验数据
        $vld = MyValidate::makeValidate(['username','password']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
//       启动redis
        $redis = new Redis();
        $checkUser = MyValidate::checkUserExistsByUsername($redis,$p['username']);
        if(is_numeric($checkUser)){
            return res('用户名已存在');
        }
//        2、数据准备
        $d = [
            'session'   =>  getNewSession($p['username']),
            'username'  =>  $p['username'],
            'password'  =>  getPassword($p['password']),
            'nickname'  =>  $p['username'],
            'gender'    =>  1,
            'preference'    =>  1,
            'avatar'    =>  ''
        ];
//       获取用户唯一标识id
        $uid = $redis->increaseUserCount();
//        3、用户数据入库
        $res = $redis->updateUserInfo($uid,$d);
//       session和用户id的映射,过期时间一小时
        $res1 = $redis->setSession($d['session'],$uid);
//        用户名和id的映射,不过期
        $res2 = $redis->setUsernameToId($p['username'],$uid);
//        4、成功返回，否则回滚
        if($res && $res1 && $res2){
//            去掉不需要的数据
            $info = $d;
            unset($info['username']);
            unset($info['password']);
            unset($info['avatar']);
            return res('注册成功',1,$info);
        }else{
//            回滚，删除相应的值
            $redis->deleteUserByUid($uid);
            $redis->deleteSession($d['session']);
            $redis->deleteUsernameToIdByUsername($p['username']);
            return res('注册失败',0,[$res,$res1,$res2]);
        }

    }
}
