<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/5/28
 * Time: 13:09
 */

namespace app\index\controller;

use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Image
{

    /**
     * 上传一个任务中的图片
     * 1、校验数据，如果任务名不存在则新建，并初始化相关信息
     * 2、上传、压缩图片，生成缩略图
     * 3、图片数据入库
     * 4、任务数据更新
     * @return string
     */
    public function uploadImages()
    {
//        1、校验数据
        $vld = MyValidate::makeValidate(['session','taskName']);
        if($vld !== true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        如果任务名不存在则新建，并初始化相关信息
        $tid = MyValidate::checkTaskExistByTaskName($redis,$p['taskName']);
        if(!is_numeric($tid)){
            $tid = $redis->increaseTaskCount();
            $redis->setTaskNameToId($p['taskName'],$tid);
            $data = [
                'count'     =>  0,
                'admin_id'  =>  $uid,
                'create_time'   =>  time(),
                'finished'  =>  0,
            ];
            $redis->updateTaskInfo($tid,$data);
        }
//        获取任务基本信息，用于后面的修改
        $taskInfo = $redis->getTaskInfoByTid($tid,['count']);
//        2、上传图片
        $images = request()->file();
        $status = [];
        foreach($images as $image){
//            move一次后不能再次move，只能拷贝
            $info = $image->move(config('IMAGES_PATH'));
            if($info){
//                图片压缩(80)
                $save_path = getImagesFullPath($info->getSaveName());
                $img = Img::open($save_path);
                $img->save($save_path);
//                接下来拷贝一份做缩略图，先判断存放文件夹是否存在
//                如果文件夹没有创建，则新建，这里的缩略图
                $save_thumb_dir = config('IMAGES_THUMB_PATH').DS.date('Ymd');
                if(!is_dir($save_thumb_dir)){
                    mkdir($save_thumb_dir,755,true);
                }
//                先拷贝一份到缩略图文件夹中，之后才能进行操作
                $save_thumb_path = getImagesThumbFullPath($info->getSavename());
                if(copy($save_path,$save_thumb_path)){
//                生成缩略图
                    $img_thumb = Img::open($save_thumb_path);
                    $img_thumb->thumb(250,250)->save($save_thumb_path,null,92);
                }
//                3、图片数据入库
                $pid = $redis->increasePictureCount();
                $data = [
                    'name'  =>  $info->getFilename(),
                    'width' =>  $img->width(),
                    'height'=>  $img->height(),
                    'task_id'   =>  $tid,
                    'category_id'   =>  1,
                    'save_path' =>  $info->getSaveName(),
                    'finished'  =>  0,
                    'tag_time'  =>  0
                ];
                $redis->updatePictureInfo($pid,$data);
//                4、任务数据更新
                $data = [
                    'count' =>  $taskInfo['count']+1
                ];
                $redis->updateTaskInfo($tid,$data);
//                添加到未完成标记中
                $redis->updateTaskIncludingId($tid,$pid);
//                添加到任务所有图片中
                $redis->updateTaskPictureId($tid,$pid);
                $status[] = 'ok,success';
            }else{
                $status[] = 'no,failed';
            }
        }
        return res('上传完成',1,$status);
    }

}