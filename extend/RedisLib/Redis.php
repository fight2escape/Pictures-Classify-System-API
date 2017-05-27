<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/5/25
 * Time: 14:07
 */
namespace RedisLib;

class Redis
{
    private $handler;
    private $option=[
        'host'  =>  '127.0.0.1',
        'port'  =>  7200,
        'auth'  =>  'huawei_img_redis_pwd'
    ];

    public function __construct()
    {
        $this->handler = new \Redis();
        $op = $this->option;
        $this->handler->connect($op['host'],$op['port']);
        $this->handler->auth($op['auth']);
    }


    /**
     * **********************************************************
     * **********************************************************
     * 封装业务函数
     * **********************************************************
     * **********************************************************
     */

    /**
     * **********************************************************
     * GET  查询
     * **********************************************************
     */



    /**
     * 根据uid获取用户数据（user:$uid)
     * 如果有指定字段，则获取相应的值
     * 否则获取全部的值
     * @param $uid
     * @param array $keyArr
     * @return array
     */
    public function getUserInfoByUid($uid,$keyArr=[])
    {
        if(!empty($keyArr)){
            $res = $this->handler->hMGet($this->getKeyUser($uid),$keyArr);
        }else{
            $res = $this->handler->hGetAll($this->getKeyUser($uid));
        }
        return $res;
    }


    /**
     * 通过username找到uid
     * @param $username
     * @return string
     */
    public function getUidByUsername($username)
    {
        return $this->handler->hGet($this->getKeyUsernameToId(),$username);
    }

    /**
     * 通过session获取uid
     * @param $session
     * @return bool|string
     */
    public function getUidBySession($session)
    {
        return $this->handler->get($this->getKeySession($session));
    }
    /**
     *
     * 获取各种键的全名
     * **********************************************************
     */

    /**
     * 用户名到ID的映射字段名
     * @return string
     */
    public function getKeyUsernameToId()
    {
        return 'username.to.id';
    }

    /**
     * session字段名
     * @param $session
     * @return string
     */
    public function getKeySession($session)
    {
        return "session:{$session}";
    }

    /**
     * user字段名（用户信息）
     * @param $uid
     * @return string
     */
    public function getKeyUser($uid)
    {
        return "user:{$uid}";
    }

    /**
     * **********************************************************
     * SET  新增、自增
     * **********************************************************
     */

    /**
     * 设置用户名到uid的映射
     * @param $username
     * @param $uid
     * @return int
     */
    public function setUsernameToId($username,$uid)
    {
        return $this->handler->hSet($this->getKeyUsernameToId(),$username,$uid);
    }

    /**
     * 设置新的session值
     * 过期时间一小时
     * @param $session
     * @param $uid
     * @return bool
     */
    public function setSession($session,$uid)
    {
//        测试中先设置成永久有效
//        return $this->handler->set($this->getKeySession($session),$uid,config('SESSION_TIMEOUT'));
        return $this->handler->set($this->getKeySession($session),$uid);
    }

    /**
     * 自增用户数量，并返回自增后的值
     * 可用作用户唯一标识uid
     * @return int
     */
    public function increaseUserCount()
    {
        return $this->handler->incr('users:count');
    }

    /**
     * **********************************************************
     * UPDATE   更新
     * **********************************************************
     */

    /**
     * 根据uid更新用户信息
     * @param $uid
     * @param $data
     * @return bool
     */
    public function updateUserInfo($uid,$data)
    {
        return $this->handler->hMset($this->getKeyUser($uid),$data);
    }


    /**
     * **********************************************************
     * DELETE   删除
     * **********************************************************
     */

    /**
     * 根据uid删除用户 user:$uid
     * @param $uid
     */
    public function deleteUserByUid($uid)
    {
        return $this->handler->delete($this->getkeyuser($uid));
    }

    /**
     * 删除session
     * @param $session
     */
    public function deleteSession($session)
    {
        return $this->handler->delete($this->getKeySession($session));
    }

    /**
     * 删除用户名与uid的映射关系
     * @param $username
     * @return int
     */
    public function deleteUsernameToIdByUsername($username)
    {
        return $this->hDel($this->getKeyUsernameToId(),$username);
    }











    /**
     * **********************************************************
     * **********************************************************
     * 封装部分Redis基本操作
     * **********************************************************
     * **********************************************************
     */

    /**
     * key  操作
     */

    public function setKey($key,$value,$timeout=0)
    {
        return $this->handler->set($key,$value,$timeout);
    }


    /**
     * string 操作
     */

    public function delete($key)
    {
        return $this->handler->delete($key);
    }

    public function set($key,$val)
    {
        return $this->handler->set($key,$val);
    }

    public function get($key)
    {
        return $this->handler->get($key);
    }

    public function incr($key)
    {
        return $this->handler->incr($key);
    }

    /**
     *  Hash 操作
     */

    public function hExists($key,$hashKey)
    {
        return $this->handler->hExists($key,$hashKey);
    }

    public function hGet($key,$hashKey)
    {
        return $this->handler->hGet($key,$hashKey);
    }

    public function hMGet($key,$hashKeyArray)
    {
        return $this->handler->hMGet($key,$hashKeyArray);
    }

    public function hGetAll($key)
    {
        return $this->handler->hGetAll($key);
    }

//    public function hSet($key,$hashKey,$value)
//    {
//        return $this->handler->hSet($key,$hashKey,$value);
//    }

    public function hMset($key,$data)
    {
        return $this->handler->hMset($key,$data);
    }

    public function hDel($key,$hashKey)
    {
        return $this->handler->hDel($key,$hashKey);
    }
}