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

    public function exportTag()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $data = [
            'test'  =>  'ok,yes,no,good\n',
            'time'  =>  time().'\n',
            'what'  =>  'how'
        ];
        file_put_contents('output_test.json',$data,FILE_APPEND);
        $data = [
            'url'   =>  'https://img.fight2escape.club/output_test.json'
        ];
        return res('获取成功',1,$data);
    }

    /**
     * 获取含有某标签的图片
     * @return string
     */
    public function getTag()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        数据加工
        $name = $p['name']??'';
        $page = $p['page']??0;
        $count = $p['count']??10;
//        条件为标签名
        $where = [];
        $where['label'] = ['like','%'.$name.'%'];
//        直接获取相关图片
        $images = db('label')
            ->alias('lb')
            ->where($where)
            ->join('picture pic','lb.picture_id = pic.id')
            ->field('pic.id,pic.path')
            ->order('accepted desc,count desc')
            ->page($page,$count)
            ->select();
        $total = db('label')
            ->alias('lb')
            ->where($where)
            ->count();
        $data = [
            'total'     =>  $total,
            'images'    =>  $images
        ];
        return $images?res('该标签下的图片获取成功',1,$data):res('相关图片获取失败');
    }

    /**
     * 获取标签列表
     * @return string
     */
    public function getTags()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        数据加工
        $name = $p['name']??'';
        if(isset($p['name'])){
            preg_match_all("/./u",$name,$tmp);
            $name = implode('%',$tmp[0]);
        }
        $page = $p['page']??1;
        $count = $p['count']??10;
        $where = [];
        $where['label']  = ['like','%'.$name.'%'];
//        1、先获取总数量
        $total = db('label')
            ->alias('lb')
            ->where($where)
            ->join('picture pic','lb.picture_id = pic.id')
            ->group('label')
            ->count();
//        2、根据标签进行分组，同时随机获取相关的第一张图片的路径
        $info = db('label')
            ->alias('lb')
            ->where($where)
            ->join('picture pic','lb.picture_id = pic.id')
            ->field('lb.label as name,pic.path')
            ->group('label')
            ->order('count(*) desc,lb.count')
            ->page($page,$count)
            ->select();
        $data = [
            'total' =>  $total,
            'tags'  =>  $info
        ];
        return $total&&$info?res('标签列表获取成功',1,$data):res('标签列表获取失败');
    }
}