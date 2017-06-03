<?php
namespace app\index\model;
use think\Model;

class User extends Model
{
    /**
     * 通过ID获取用户信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public static function getUserInfoById($id)
    {
        return db('user')->where('id',$id)->find();
    }
}