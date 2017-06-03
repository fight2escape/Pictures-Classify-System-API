<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/5/26
 * Time: 11:56
 */

namespace app\index\model;

use think\Model;
use think\Validate;

class MyValidate extends Model
{
    private static $rule = [
//        用户相关
        'username'  =>  ['username','require|max:40','用户名不能为空|用户名过长'],
        'username_unique'   =>  ['username','require|max:40|unique:user','用户名不能为空|用户名过长|用户名已存在'],
        'password'  =>  ['password','require|min:6','密码不能为空|密码不能少于6位'],
        'session'   =>  ['session','require','session不能为空'],
        'nickname'  =>  ['nickname','require','昵称不能为空'],
        'gender'    =>  ['gender','require|in:1,2,3','性别不能为空|性别非法'],
        'preference'=>  ['preference','require|in:1,2','推送开关不能为空|推送开关值非法'],
        'path'      =>  ['path','require','图片路径不能为空'],
        'taskName'  =>  ['taskName','require','任务名不能为空'],
//        图片相关
        'type_id'   =>  ['type_id','number','类型id应该为数字'],
        'page'      =>  ['page','number','分页页码应为数字'],
        'count'     =>  ['count','number','每页数量应为数字'],
    ];


    /**
     * 判断session是否有效
     * 有效则返回uid，否则提示错误
     * @param $redis
     * @param $session
     * @return string
     */
    public static function checkSessionExistBySession($redis,$session)
    {
        return $redis->getUidBySession($session)?:'身份已过期，请重新登录';
    }


    /**
     * 验证字段并返回结果
     * 先获取验证规则
     * 进行检查后返回验证结果
     * @param $arr
     * @return array|bool
     */
    public static function makeValidate($arr)
    {
        $rule = self::getRules($arr);
        $vld = new Validate($rule);
        $res = $vld->check(input('post.'));
        return $res?true:$vld->getError();
    }

    /**
     * 获取验证规则
     * 返回rule=[]数组
     * @param $arr  验证规则
     * @return array
     */
    public static function getRules($arr)
    {
        $rule = [];
        foreach($arr as $k){
            $rule[] = self::$rule[$k];
        }
        return $rule;
    }
}