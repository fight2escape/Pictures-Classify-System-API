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
     * 根据id获取用户数据（task:$id)
     * 如果有指定字段，则获取相应的值
     * 否则获取全部的值
     * @param $id
     * @param array $hashKeyArr
     * @return array
     */
    public function getTaskInfoByTid($id,$hashKeyArr=[])
    {
        return $this->getInfoByKey($this->getKeyTask($id),$hashKeyArr);
    }

    /**
     * 根据id获取用户数据（user:$id)
     * 如果有指定字段，则获取相应的值
     * 否则获取全部的值
     * @param $id
     * @param array $hashKeyArr
     * @return array
     */
    public function getUserInfoByUid($id,$hashKeyArr=[])
    {
        return $this->getInfoByKey($this->getKeyUser($id),$hashKeyArr);
    }

    /**
     * 如果有指定字段，则获取相应的值
     * 否则获取全部的值
     * @param $key
     * @param array $hashKeyArr
     * @return array
     */
    public function getInfoByKey($key,$hashKeyArr=[])
    {
        if(!empty($hashKeyArr)){
            $res = $this->handler->hMGet($key,$hashKeyArr);
        }else{
            $res = $this->handler->hGetAll($key);
        }
        return $res;
    }


    /**
     * 通过任务名获取到任务id
     * @param $taskName
     * @return string
     */
    public function getTidByTaskName($taskName)
    {
        return $this->handler->hGet($this->getKeyTaskNameToId(),$taskName);
    }

    /**
     * 通过username找到id
     * @param $username
     * @return string
     */
    public function getUidByUsername($username)
    {
        return $this->handler->hGet($this->getKeyUsernameToId(),$username);
    }

    /**
     * 通过session获取id
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
     * 图片信息键名"picture:$pid"
     * @param $id
     * @return string
     */
    public function getKeyPicture($id)
    {
        return "picture:{$id}";
    }

    /**
     * 任务中包含的所有图片ID
     * @param $id
     * @return string
     */
    public function getKeyTaskPictureId($id)
    {
        return "task:{$id}.picture.id";
    }

    /**
     * 任务中未完成标记的图片键名
     * @param $id
     * @return string
     */
    public function getKeyTaskIncludingId($id)
    {
        return "task:{$id}.including.id";
    }

    /**
     * 任务信息键名
     * @param $id
     * @return string
     */
    public function getKeyTask($id)
    {
        return "task:{$id}";
    }

    /**
     * 任务名到任务的映射
     * @return string
     */
    public function getKeyTaskNameToId()
    {
        return 'taskName.to.id';
    }

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
     * @param $id
     * @return string
     */
    public function getKeyUser($id)
    {
        return "user:{$id}";
    }

    /**
     * **********************************************************
     * SET  新增、自增
     * **********************************************************
     */


    /**
     * 设置任务名到其id的映射
     * @param $taskName
     * @param $id
     * @return int
     */
    public function setTaskNameToId($taskName,$id)
    {
        return $this->handler->hSet($this->getKeyTaskNameToId(),$taskName,$id);
    }

    /**
     * 设置用户名到id的映射
     * @param $username
     * @param $id
     * @return int
     */
    public function setUsernameToId($username,$id)
    {
        return $this->handler->hSet($this->getKeyUsernameToId(),$username,$id);
    }

    /**
     * 设置新的session值
     * 过期时间一小时
     * @param $session
     * @param $id
     * @return bool
     */
    public function setSession($session,$id)
    {
//        测试中先设置成永久有效
//        return $this->handler->set($this->getKeySession($session),$id,config('SESSION_TIMEOUT'));
        return $this->handler->set($this->getKeySession($session),$id);
    }

    /**
     * 自增图片数量，并返回自增后的值
     * 作为任务的唯一标识
     * @return int
     */
    public function increasePictureCount()
    {
        return $this->handler->incr('picture:count');
    }

    /**
     * 自增任务数量，并返回自增后的值
     * 作为任务的唯一标识
     * @return int
     */
    public function increaseTaskCount()
    {
        return $this->handler->incr('task:count');
    }

    /**
     * 自增用户数量，并返回自增后的值
     * 可用作用户唯一标识id
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
     * 根据id更新图片信息
     * @param $id
     * @param $data
     * @return bool
     */
    public function updatePictureInfo($id,$data)
    {
        return $this->handler->hMset($this->getKeyPicture($id),$data);
    }

    /**
     * 根据tid添加到图片ID到任务的所有图片ID集
     * @param $tid
     * @param $pid
     * @return int
     */
    public function updateTaskPictureId($tid,$pid)
    {
        return $this->handler->sAdd($this->getKeyTaskPictureId($tid),$pid);
    }

    /**
     * 根据tid任务中未完成标记的图片pid集
     * @param $tid
     * @param $pid
     * @return int
     */
    public function updateTaskIncludingId($tid,$pid)
    {
        return $this->handler->sAdd($this->getKeyTaskIncludingId($tid),$pid);
    }

    /**
     * 根据id更新任务信息
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateTaskInfo($id,$data)
    {
        return $this->handler->hMset($this->getKeyTask($id),$data);
    }

    /**
     * 根据id更新用户信息
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateUserInfo($id,$data)
    {
        return $this->handler->hMset($this->getKeyUser($id),$data);
    }


    /**
     * **********************************************************
     * DELETE   删除
     * **********************************************************
     */

    /**
     * 根据id删除用户 user:$id
     * @param $id
     */
    public function deleteUserByUid($id)
    {
        return $this->handler->delete($this->getKeyUser($id));
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
     * 删除用户名与id的映射关系
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