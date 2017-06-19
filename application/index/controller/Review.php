<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/6/19
 * Time: 9:23
 */

namespace app\index\controller;

use think\Controller;
use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Review
{

    /**
     * 将待审核标签标为不通过
     * @return string
     */
    public function reject()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $vld = MyValidate::makeValidate(['id','old_label','new_label']);
        if($vld !== true){ return res($vld); }
        $p = input('post.');
//        将状态设置为2
        $where = [
            'picture_id'    =>  $p['id'],
            'old_label'     =>  $p['oldLabel'],
            'new_label'     =>  $p['newLabel']
        ];
        $res = db('correction')->where($where)->setField('status',2);
        return $res?res('成功设置为不通过',1,[]):res('设置为不通过失败');
    }

    /**
     * 将待审核标签标为通过
     * @return string
     */
    public function accept()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $vld = MyValidate::makeValidate(['id','old_label','new_label']);
        if($vld !== true){ return res($vld); }
        $p = input('post.');
//        将状态设置为1，同时更改标签结果
        $where = [
            'picture_id'    =>  $p['id'],
            'old_label'     =>  $p['oldLabel'],
            'new_label'     =>  $p['newLabel']
        ];
        $res = db('correction')->where($where)->setField('status',1);
        if($res){
            $where = [
                'picture_id'    =>  $p['id'],
                'label'         =>  $p['oldLabel']
            ];
            $update = [
                'label'     =>  $p['newLabel']
            ];
            $res2 = db('label')
                ->where($where)
                ->update($update);
            return $res2?res('标签修改成功',1,[]):res('标签修改失败');
        }else{
            return res('标签接受失败');
        }
    }

    /**
     * 获取待审核标签
     * @return string
     */
    public function getReview()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
        $page = $p['page']??1;
        $count = $p['count']??10;
        $where = [
            'status'    =>  0
        ];
//        获取数据
        $images = db('correction')
            ->alias('c')
            ->where($where)
            ->join('picture pic','c.picture_id = pic.id')
            ->field('pic.id,pic.path,c.old_label,c.new_label')
            ->page($page,$count)
            ->order('c.create_time desc')
            ->select();
//        整理成所需格式
        foreach($images as $k=>$v){
            $images[$k]['labels'] = [
                'oldLabel'  =>  $images[$k]['old_label'],
                'newLabel'  =>  $images[$k]['new_label']
            ];
            unset($images[$k]['old_label']);
            unset($images[$k]['new_label']);
        }
        $total = db('correction')->where($where)->count();
        $data = [
            'images'    =>  $images,
            'total'     =>  $total
        ];
        return res('待审核标签获取成功',1,$data);
    }
}