<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/6/7
 * Time: 16:19
 */

namespace app\index\controller;

use think\Controller;
use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Task
{
    /**
     * 根据图片名输出该图片的结构化数据
     * （图片名、结束时间、标签组）
     * 输出JSON格式
     * @return string
     */
    public function getLabelsByPictureName()
    {
        $name = input('post.name');
        $picture = db('picture')->where('name',$name)->field('id,name,finished_time as time')->find();
        $labels = db('label')
            ->where('picture_id', $picture['id'])
            ->order('count desc')
            ->page(1, config('ACCEPTED_COUNT'))
            ->column('label');
        $data = [
            'picture_name'  =>  $picture['name'],
            'finish_time'   =>  date('Y-m-d H:i:s',$picture['time']),
            'labels'        =>  $labels
        ];
        return res('标签获取成功',1,$data);
    }

    public function exportTask()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $vld = MyValidate::makeValidate(['id','type']);
        if($vld !== true){ return res($vld); }
        $p = input('post.');
//        根据类型判断是导出标签还是图片文件
        if(1 == $p['type']){
            $data = [
                'test'  =>  'ok,yes,no,good\n',
                'time'  =>  time().'\n',
                'what'  =>  'how'
            ];
            file_put_contents('output_test.txt',$data,FILE_APPEND);
//            $fileName = 'output_test.txt';
//            header("Content-Type:application/octet-stream");
//            header("Content-Disposition:attachment;filename=".$fileName);
//            header("Accept-ranges:bytes");
//            header("Accept-Length:".filesize($fileName));
//            $h = fopen($fileName, 'r');
//            echo fread($h, filesize($fileName));
//            fclose($h);
            $data = [
                'url'   =>  'https://img.fight2escape.club/output_test.txt'
            ];
            return res('获取成功',1,$data);
        }else if(2 == $p['type']){

        }else{
            return res('类型错误');
        }
    }

    /**
     * 获取某一任务详细信息
     * @return string
     */
    public function getTask()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        先查询任务信息
        $where = [
            'id'        =>  $p['id'],
            'status'    =>  1
        ];
        $task = db('task')->where($where)->field('id,name,description,count,count_finished as finish')->find();
//        在获取该任务下的图片信息
        $images = db('picture')->where('task_id',$task['id'])->field('id,path')->select();
        $task['images'] = $images;
        return $task&&$images?res('任务信息获取成功',1,$task):res('任务信息获取失败',0,[$task,$images]);
    }

    /**
     * 获取任务列表
     * @return string
     */
    public function getTasks()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');

        $page = $p['page']??1;
        $count = $p['count']??10;
        $where = [];
        if(isset($p['name'])){
            preg_match_all("/./u",$p['name'],$tmp);
            $name = implode('%',$tmp[0]);
            $where['ts.name'] = ['like','%'.$name.'%'];
        }

//        查询任务状态，和图片表关联获取数量
        $where = [
            'ts.status'     =>  1,
            'ts.finished'     =>  0,
            'pic.finished'      =>  1
        ];
        $task = db('task')
            ->alias('ts')
            ->where($where)
            ->join('picture pic','ts.id = pic.task_id','LEFT')
            ->field('ts.id,count(*) as finished,ts.count')
            ->group('ts.id')
            ->page($page,$count)
            ->select();
        foreach($task as $k=>$v){
            db('task')->where('id',$task[$k]['id'])->update(['count_finished'  =>  $task[$k]['finished']]);
//            更新任务状态
            if($task[$k]['count'] == $task[$k]['finished']){
                db('task')->where('id',$task[$k]['id'])->update(['finished'=>1]);
            }
        }
//        重新设置where条件，进行查询
        $where = [
            'status'    =>  1,
        ];
        if(isset($p['name'])){
            $where['name'] = ['like','%'.$p['name'].'%'];
        }
        $taskInfo = db('task')
            ->where($where)
            ->field('id,name,description,count,count_finished as finish')
            ->select();
        $total = db('task')->where($where)->count();
        $data = [
            'total' =>  $total,
            'tasks' =>  $taskInfo
        ];
        return res('任务信息拉取成功',1,$data);
    }

    /**
     * 更新任务信息
     * @return string
     */
    public function editTask()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        检查任务是否存在
        $task = db('task')->where('id',$p['id'])->find();
        if(!$task){
            return res('该任务不存在');
        }
        $update = [];
        $res1 = [];
        $res2 = [];
        $pictures = [];
        $images = explode(',',$task['images']);
//        新增图片
        if(isset($p['added'])){
//            设置对应图片的任务ID
            $where = [
                'id'    =>  ['in',$p['added']]
            ];
            $res1 = db('picture')->where($where)->setField('task_id',$task['id']);
            $images = array_merge($images,$p['added']);
        }
//        删除图片
        if(isset($p['removed'])){
//            保存图片信息
            $where = [
                'id'=>['in',$p['removed']]
            ];
            $pictures = db('picture')->where($where)->select();
//            删除图片信息
            $res2 = db('picture')->where($where)->delete();
//            删除相应文件
            foreach($pictures as $k=>$v){
                unlink(getImagesFullPath($pictures[$k]['path']));
                unlink(getImagesThumbFullPath($pictures[$k]['path']));
            }
//            设置images字符串
            $images = array_diff($images,$p['removed']);
        }
//        更新图片总数
        $update['count'] = count($images);
        $update['images'] = implode(',',$images);

        if(isset($p['name'])){
            $update['name'] = $p['name'];
        }
        if(isset($p['description'])){
            $update['description'] = $p['description'];
        }
//        执行更新操作
        $res = db('task')->where('id',$p['id'])->update($update);
        return $res?res('任务信息更新成功',1,[]):res('任务信息更新失败',0,[$res1,$res2,$pictures,$update]);
    }

    /**
     * 删除任务
     * 软删除，status置0
     * @return string
     */
    public function removeTask()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $p = input('post.');
//        执行删除
        $res = db('task')->where('id',$p['id'])->setField('status',0);
        return $res?res('删除成功',1,[]):res('该任务已经删除');
    }

    /**
     * 新增任务
     * @return string
     */
    public function createTask()
    {
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
        $vld = MyValidate::makeValidate(['name','description','images']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
//        检查是否重复提交
        $where = [
            'name'  =>  $p['name'],
            'description'   =>  $p['description'],
        ];
        if(db('task')->where($where)->find()){
            return res('该任务已存在');
        }
//        初始化相关信息
        $count = count($p['images']);
        $images_string = implode(',',$p['images']);
        $data = [
            'name'      =>  $p['name'],
            'count'     =>  $count,
            'priority'  =>  1,
            'count_finished'    =>  0,
            'admin_id'  =>  $aid,
            'description'   =>  $p['description'],
            'create_time'   =>  time(),
            'finished'  =>  0,
            'images'    =>  $images_string
        ];
        $tid = db('task')->insertGetId($data);
        if(!$tid){
            return res('新建任务失败');
        }
//        更新图片的信息（任务ID）
        $where = [
            'id'    =>  ['in',$p['images']]
        ];
        $res = db('picture')->where($where)->setField('task_id',$tid);
        return $res?res('任务创建成功',1,[]):res('任务创建失败');
    }
}