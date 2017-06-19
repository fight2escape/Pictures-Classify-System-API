<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/6/6
 * Time: 16:32
 */

namespace app\index\controller;

use think\Controller;
use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Users
{

    /**
     * 获取某一用户详细信息
     * @return string
     */
    public function getUser()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');

        $user = db('user')->where('id',$p['id'])->field('id,username,nickname,gender,email,scores,avatar')->find();
        return $user?res('用户信息获取成功',1,$user):res('用户不存在');
    }

    /**
     * 获取用户列表
     * @return string
     */
    public function getUsers()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');

        $page = $p['page']??1;
        $count = $p['count']??10;
//        如果有username则作为搜索条件进行搜索
        $username = $p['username']??'';
        $where = [
            'status'    =>  1,
            'username'  =>  ['like','%'.$username.'%']
        ];
//        总数
        $total = db('user')->where($where)->count();
        $users = db('user')
            ->where($where)
            ->field('id,username,nickname,gender,email,scores,avatar')
            ->page($page,$count)
            ->select();
        $data = [
            'total' =>  $total,
            'users'    =>  $users
        ];
        return res('用户列表拉取成功',1,$data);
    }

    /**
     * 更新用户信息
     * @return string
     */
    public function editUser()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        添加更新条件
        $update = [];
        if(isset($p['username'])){
            $where = [
                'username'  =>  $p['username'],
                'id'    =>  ['<>',$p['id']]
            ];
            $admin = db('admin')->where($where)->find();
            if($admin){
                return res('用户名已存在');
            }else{
                $update['username'] = $p['username'];
            }
        }
        if(isset($p['password'])){
            $update['password'] = getPassword($p['password']);
        }
        if(isset($p['nickname'])){
            $update['nickname'] = $p['nickname'];
        }
        if(isset($p['gender'])){
            $update['gender'] = $p['gender'];
        }
        if(isset($p['email'])){
            $update['email'] = $p['email'];
        }
//        执行更新操作
        $res = db('user')->where('id',$p['id'])->update($update);
        return $res?res('用户信息更新成功',1,[]):res('用户信息更新失败');
    }

    /**
     * 删除用户
     * @return string
     */
    public function removeUser()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        先判断用户是否存在（状态为1）
        $where = [
            'id'    =>  $p['id'],
            'status'    =>  ['=',1]
        ];
        $user = db('user')->where($where)->find();
        if(!$user){
            return res('用户已删除',1,[]);
        }
//        设置状态为0
        $res = db('user')->where('id',$p['id'])->setField('status',0);
        return $res?res('用户删除成功',1,[]):res('用户删除失败');
    }


    /**
     * 新增用户
     * @return string
     */
    public function createUser()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $vld = MyValidate::makeValidate(['username_unique','password','nickname','gender','email']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $insert = [
            'username'  =>  $p['username'],
            'password'  =>  getPassword($p['password']),
            'nickname'  =>  $p['nickname'],
            'gender'    =>  $p['gender'],
            'email'     =>  $p['email'],
            'avatar'    =>  config('USER_AVATAR'),
            'create_time'   =>  time()
        ];
        $res = db('user')->insert($insert);
        return $res?res('用户添加成功',1,[]):res('用户添加失败');
    }
}