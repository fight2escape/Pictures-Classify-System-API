<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/6/6
 * Time: 13:09
 */

namespace app\index\controller;

use think\Controller;
use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Admins
{

    public function getAdmin()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');

        $admin = db('admin')->where('id',$p['id'])->field('id,username,email,last_login_time as last,super')->find();
        return $admin?res('管理员信息拉取成功',1,$admin):res('管理员信息拉取失败');
    }

    /**
     * 获取管理员列表
     * @return string
     */
    public function getAdmins()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');

        $page = $p['page']??0;
        $count = $p['count']??4;
//        如果有username则作为搜索条件进行搜索
        $username = $p['username']??'';
        $where = [
            'username'  =>  ['like','%'.$username.'%']
        ];
//        管理员总数
        $total = db('admin')->count();
        $admins = db('admin')
            ->alias('am')
            ->where($where)
            ->field('am.id,am.username,am.email,am.last_login_time as last,am.super')
            ->limit($page*$count,$count)
            ->select();
//        逐一与task表关联，获取已发布的数量
        foreach($admins as $k=>$v){
            $admins[$k]['task'] = db('task')->where('admin_id',$admins[$k]['id'])->count();
        }
        $data = [
            'total' =>  $total,
            'admins'    =>  $admins
        ];
        return res('管理员列表拉取成功',1,$data);

    }


    /**
     * 更新管理员信息
     * @return string
     */
    public function editAdmin()
    {
//        超级管理员？
        $aid = MyValidate::checkAdminIsSuperByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        因为各个项非必须，所以逐个判断
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
        if(isset($p['email'])){
            $update['email'] = $p['email'];
        }
        if(isset($p['super'])){
            $update['super'] = $p['super'];
        }
        $res = db('admin')->where('id',$p['id'])->update($update);
        return $res?res('管理员信息更新成功',1,[]):res('管理员信息没有改变');
    }

    /**
     * 删除管理员
     * @return string
     */
    public function removeAdmin()
    {
//        超级管理员？
        $aid = MyValidate::checkAdminIsSuperByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $vld = MyValidate::makeValidate(['id']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
//        检查是否存在
        if(!db('admin')->where('id',$p['id'])->find()){
            return res('要删除的管理员不存在');
        }
//        进行删除
        $res = db('admin')->where('id',$p['id'])->delete();
        return $res?res('删除成功',1,[]):res('删除失败');
    }


    /**
     * 新增管理员
     * @return string
     */
    public function createAdmin()
    {
//        超级管理员？
        $aid = MyValidate::checkAdminIsSuperByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $vld = MyValidate::makeValidate(['administrator_unique','password','email','super']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');

        $data = [
            'cookie'    =>  '',
            'username'  =>  $p['username'],
            'password'  =>  getPassword($p['password']),
            'super'     =>  $p['super'],
            'email'     =>  $p['email'],
            'create_time'   =>  time(),
            'update_time'   =>  time()
        ];
        $res = db('admin')->insert($data);
        return $res?res('管理员新增成功',1,[]):res('管理员新增失败');

    }


    /**
     * 查询管理员登陆状态
     * @return string
     */
    public function query()
    {
//        找到相应的管理员
        $admin = db('admin')->where('cookie',cookie('admin'))->field('id,username,super')->find();
//        更新cookie过期时间
        cookie('admin',cookie('admin'),config('COOKIE_ADMIN'));
        return $admin?res('管理员已登录',1,$admin):res('管理员未登录');
    }

    /**
     * 管理员注销
     * @return string
     */
    public function logout()
    {
//        先检查管理员是否存在
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
//        清除表中cookie
        $res = db('admin')->where('id',$aid)->setField('cookie','');
//        清除cookie
        cookie('admin',null);
        return $res?res('管理员注销成功',1,[]):res('注销失败');
    }

    /**
     * 管理员登录
     * @return string
     */
    public function login()
    {
        $vld = MyValidate::makeValidate(['username','password']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
//        检查用户是否存在
        $where = [
            'username'  =>  $p['username'],
            'password'  =>  getPassword($p['password'])
        ];
        $admin = db('admin')->where($where)->field('id,username,super')->find();
        if(!$admin){
            return res('用户名或密码错误');
        }else{
//            生成新cookie并返回
            $cookie = encrypt($p['username']);
            cookie('admin',$cookie,config('COOKIE_ADMIN'));
            $update = [
                'cookie'    =>  $cookie,
                'last_login_time'   =>  time(),
                'update_time'   => time()
            ];
            $res = db('admin')->where($where)->update($update);
            return $res?res('登陆成功',1,$admin):res('管理员信息更新失败');
        }
    }
}