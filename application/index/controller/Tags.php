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
        $tagName = input('post.name')??'';
//        条件为标签名
        $where = [];
        $where['label'] = ['like','%'.$tagName.'%'];
        $where['accepted'] = 1;
        //        直接获取相关图片
        $images = db('label')
            ->where($where)
            ->field('picture_id')
            ->select();
        $imageIds = getIds($images, 'picture_id');

        $data = [
            'url'   =>  $this->getExportUrl($imageIds)
        ];
        return res('获取成功',1,$data);
    }

    // 根据ids获取需要导出的数据
    public function getExportUrl($ids, $fileName = 'output_label')
    {
        $fileName .= '.csv';
        $res = [];
        if(!empty($ids)){
            // 获取图片路径
            $where = [];
            $where['id'] = ['in',$ids];
            $pathArr = db('picture')
                ->where($where)
                ->field('id,path')
                ->select();
            $pathArr = mkKeyAssoc($pathArr, 'id', 'path');
            // 获取标签
            $where = [];
            $where['picture_id'] = ['in',$ids];
            $where['accepted'] = 1;
            $labels = db('label')
                ->where($where)
                ->field('picture_id,
            group_concat(label order by count desc separator \'; \') as labels')
                ->group('picture_id')
                ->select();
            $labels = mkKeyAssoc($labels, 'picture_id', 'labels');
            // 合并路径和标签
            foreach($pathArr as $key=>$val){
                $res[$key] = [
                    'path'  =>  $val,
                    'labels'    =>  $labels[$key]
                ];
            }
        }
        // 输出csv格式
        $csv = 'id,url,labels'.PHP_EOL;
        $i = 1;
        foreach($res as $row){
            $csv .= sprintf('%s,%s,%s%s', $i++, $row['path'], $row['labels'], PHP_EOL);
        }
        file_put_contents($fileName, $csv);
        return 'https://img.fight2escape.club/' . $fileName;
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