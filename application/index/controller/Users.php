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

    public function editUser()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
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
        if(isset($p['nickname'])){
            $update['nickname'] = getPassword($p['nickname']);
        }
        if(isset($p['gender'])){
            $update['gender'] = getPassword($p['gender']);
        }
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
        $vld = MyValidate::makeValidate(['username_unique','password','nickname','gender']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $insert = [
            'username'  =>  $p['username'],
            'password'  =>  getPassword($p['password']),
            'nickname'  =>  $p['nickname'],
            'gender'    =>  $p['gender'],
            'create_time'   =>  time()
        ];
        $res = db('user')->insert($insert);
        return $res?res('用户添加成功',1,[]):res('用户添加失败');
    }
}