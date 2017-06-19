<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/6/19
 * Time: 20:30
 */

namespace app\index\controller;

use think\Controller;
use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Tags
{

    public function getTags()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        数据加工
        $page = $p['page']??1;
        $count = $p['count']??10;
        $where = [];
        if(isset($p['name'])){
            $where['name']  = ['like','%'.$p['name'].'%'];
        }
//        1、先获取一页的数据，然后对数据进行加工
        $total = db('label')
            ->group('label')
            ->count();
        $times = ceil($total/$count);
        $info = db('label')
            ->alias('lb')
            ->join('picture pic','lb.picture_id = pic.id')
            ->field('lb.label as name,pic.path')
            ->order('accepted desc,count desc,update_time desc')
            ->page($page,$count*$times)
            ->select();

    }
}