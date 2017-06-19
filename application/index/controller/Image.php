<?php
namespace app\index\controller;

use think\Controller;
use RedisLib\Redis;
use app\index\model\MyValidate;
use think\Image as Img;

class Image extends Controller
{

    /**
     * 提交纠错
     * @return string
     */
    public function postCorrection()
    {
        $vld = MyValidate::makeValidate(['session','id','old_label','new_label']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        检查是否已经存在
        $where = [
            'user_id'   =>  $uid,
            'picture_id'    =>  $p['id'],
            'old_label' =>  $p['oldLabel']
        ];
        $exist = db('correction')->where($where)->find();
        if($exist){
            $update = [
                'old_label' =>  $p['oldLabel'],
                'new_label' =>  $p['newLabel'],
                'status'    =>  0
            ];
            $res = db('correction')->where($where)->update($update);
            if(!$res){
                return res('该纠错已经提交过了');
            }
        }else{
//        入库等待处理
            $insert = [
                'user_id'   =>  $uid,
                'picture_id'    =>  $p['id'],
                'old_label' =>  $p['oldLabel'],
                'new_label' =>  $p['newLabel'],
                'status'    =>  0,
                'create_time'   =>  time()
            ];
            $res = db('correction')->insert($insert);
        }
        return $res?res('纠错提交成功',1,[]):res('纠错提交失败');
    }


    /**
     * 获取待纠错标签
     * @return string
     */
    public function getCorrection()
    {
        $vld = MyValidate::makeValidate(['session','page','count']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
//        if(!is_numeric($uid)){ return res($uid); }
//       数据加工
        $page = $p['page']??0;
        $count = $p['count']??10;
//        1、限制图片查询条件
        $where = [];
        $where['finished'] = ['=',1];
//        2、查询用户收藏过的所有图片ID
        if($uid){
            $collected = db('collected')->where('user_id',$uid)->column('picture_id');
        }else{
            $collected = [];
        }
        return $this->getImageByWhere($where,$page,$count,$collected);
    }

        /**
         * 获得某用户 未完成/审核中 标签
         * @return string
         */
        public function getUnFinishedImage()
    {
        $vld = MyValidate::makeValidate(['session','page','count']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//       数据加工
        $page = $p['page']??1;
        $count = $p['count']??10;
//        1、限制图片查询条件
        $where = [];
        $where['finished'] = ['<>',1];
        $where['user_id'] = $uid;
//        2、获取相关图片
//        从label_log中得到用户打过的所有图片，和picture表连接，得到其中已完成的图片
//        再和label表连接，得到已完成图片各自的接受标签
        $images = db('label_log')
            ->alias('lbl')
            ->where($where)
            ->join('picture pic','pic.id = lbl.picture_id','LEFT')
//            ->join('label lb','lb.picture_id = lbl.picture_id','LEFT')
            ->field('pic.id,path,width,height,taged_count as tagedCount,collected_count as collectedCount,
            lbl.labels as tags')
            ->page($page,$count)
            ->select();
        if($images){
//            3、将字符串逐个转换成数组，变量需要使用$images才能修改真实值
            foreach($images as $k => $v){
                $images[$k]['tags'] = explode(',',$images[$k]['tags']);
            }
            $data['images'] = $images;
            return res('推送成功',1,$data);
        }else{
            return res('推送失败');
        }
    }

    /**
     * 获得某用户已完成标签
     * @return string
     */
    public function getFinishedImage()
    {
        $vld = MyValidate::makeValidate(['session','page','count']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//       数据加工
        $page = $p['page']??1;
        $count = $p['count']??10;
//        1、限制图片查询条件
        $where = [];
        $where['finished'] = ['=',1];
        $where['user_id'] = $uid;
        $where['accepted'] = 1;
//        2、获取相关图片
//        从label_log中得到用户打过的所有图片，和picture表连接，得到其中已完成的图片
//        再和label表连接，得到已完成图片各自的接受标签
        $images = db('label_log')
            ->alias('lbl')
            ->where($where)
            ->join('picture pic','pic.id = lbl.picture_id','LEFT')
            ->join('label lb','lb.picture_id = lbl.picture_id','LEFT')
            ->field('pic.id,path,width,height,taged_count as tagedCount,collected_count as collectedCount,
            lbl.labels as tags,
            group_concat(lb.label order by lb.count desc separator \',\') as labels')
            ->page($page,$count)
            ->order('lbl.create_time')
            ->select();
        if($images){
//            3、进行交集差集运算，得到被接受的标签和未被接受的标签
            foreach($images as $k => $v){
//                去掉空的数据
                if($images[$k]['id'] == null){
                    unset($images[$k]);
                }else{
//                将字符串逐个转换成数组，变量需要使用$images才能修改真实值
                    $tags = explode(',',$images[$k]['tags']);
                    $labels = explode(',',$images[$k]['labels']);
                    $images[$k]['tags_accepted'] = array_intersect($tags,$labels);
                    $images[$k]['tags_rejected'] = array_diff($tags,$labels);
                    unset($images[$k]['tags']);
                    unset($images[$k]['labels']);
                }
            }
            $data['images'] = $images;
            return res('推送成功',1,$data);
        }else{
            return res('推送失败');
        }
    }

    /**
     * 获得某用户已收藏图片
     *
     * @return string
     */
    public function getCollectedImage()
    {
        $vld = MyValidate::makeValidate(['session','page','count']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//       数据加工
        $page = $p['page']??1;
        $count = $p['count']??10;
//        1、限制图片查询条件
        $where = [];
        $where['user_id'] = $uid;
//        2、获取相关图片
        $images = db('collected')
            ->alias('cld')
            ->where($where)
            ->join('picture pic','cld.picture_id = pic.id','LEFT')
            ->join('picture_type pt','pic.type_id = pt.id','LEFT')
            ->join('label lb','pic.id = lb.picture_id','LEFT')
            ->field('pic.id,path,width,height,
            pt.id as type_id,pt.name as type_name,
            group_concat(lb.label order by lb.count desc separator \',\') as labels')
            ->group('pic.id')
            ->page($page,$count)
            ->select();
        if($images){
//            3、将字符串逐个转换成数组，变量需要使用$images才能修改真实值
            foreach($images as $k => $v){
                if(isset($images[$k]['labels'])){
                    $labels = explode(',',$images[$k]['labels']);
                }else{
                    $labels = [];
                }
//              数组截取前几个
                $images[$k]['labels'] = array_slice($labels,0,5);
//              判断是否在收藏列表里
                $images[$k]['collected'] = true;
            }
            $data['images'] = $images;
            return res('推送成功',1,$data);
        }else{
            return res('推送失败');
        }
    }


    /**
     * 获取包含该标签的图片（与根据分类ID获取图片类似）
     * 1、限制图片查询条件
     * 2、查询用户收藏过的所有图片ID
     * @return string
     */
    public function getImageByLabel()
    {
        $vld = MyValidate::makeValidate(['session','label','page','count']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
//        if(!is_numeric($uid)){ return res($uid); }
//       数据加工
        $label = $p['label'];
        $page = $p['page']??0;
        $count = $p['count']??10;
//        1、限制图片查询条件
        $where = [];
        $where['finished'] = ['<>',1];
        $where['label'] = ['like','%'.$label.'%'];
//        2、查询用户收藏过的所有图片ID
        if($uid){
            $collected = db('collected')->where('user_id',$uid)->column('picture_id');
        }else{
            $collected = [];
        }
        return $this->getImageByWhere($where,$page,$count,$collected);
    }


    /**
     * 根据分类获得子分类（该大类下包含的标签）
     * 1、查表获取返回
     * 2、设置成未关注（这里不设置关注功能）
     * @return string
     */
    public function getSubCategory()
    {
        $cat_id = input('post.id');
        if(!is_numeric($cat_id)){
            return res('分类ID应为数字');
        }
//        1、查表获取返回
        $res = db('label')
            ->where('cat_id',$cat_id)
            ->field('label as name')
            ->group('label')
            ->order('SUM(count) desc')
            ->select();
        if(!$res){
            return res('子分类列表获取失败');
        }else{
//            2、设置成未关注（这里不设置关注功能）
            foreach($res as $k=>$v){
                $res[$k]['focus'] = false;
                unset($res[$k]['count']);
            }
            $list['categories'] = $res;
            return res('子分类列表获取成功',1,$list);
        }
    }


    /**
     * 用户关注或取消关注图片类型
     * 1、查看关注记录看是否已经关注
     * 2、如果要关注且记录不存在，则添加记录
     * 3、如果取消关注且记录存在，则删除记录
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function putCategoryFocus()
    {
        $vld = MyValidate::makeValidate(['session','cat_id','focus']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }

//        收藏字段默认值设为真
        $focus = $p['focus']??true;
//        查找收藏记录看是否已经收藏
        $where['user_id'] = $uid;
        $where['cat_id'] = $p['cat_id'];
        $exist = db('focus')->where($where)->find();
//        作为最终结果的判断flag
        $res = true;
//        如果要收藏且记录不存在，则添加记录
        if($focus){
            if(!$exist){
                $insert = [
                    'user_id' => $uid,
                    'cat_id'  =>  $p['cat_id'],
                    'create_time'  =>  time()
                ];
                $res = db('focus')->insert($insert);
            }
            return $res?res('关注成功',1,[]):res('关注失败');
//        如果取消关注且记录存在，则删除记录
        }else{
            if($exist){
                $res = db('focus')->where($where)->delete();
            }
            return $res?res('取消关注成功',1,[]):res('取消关注失败');
        }
    }

    /**
     * 获取分类展示列表
     * 1、查表获取返回
     * 2、如果session存在，则查询关注表
     * 3、判断是否已关注
     * @return \think\response\Json
     */
    public function getCategoryList()
    {
//        1、查表获取返回
        $p = input('post.');
        $res = db('picture_type')
            ->alias('pt')
            ->join('picture pic','pt.picture_id = pic.id')
            ->field('pt.id,pt.name,pic.path')
            ->select();
        if(!$res){
            return res('分类列表获取失败');
        }else{
//            2、如果session存在，则查询关注表
            $session = $p['session']??false;
            if($session){
                $redis = new Redis();
                $uid = MyValidate::checkSessionExistBySession($redis,$session);
                $focus = db('focus')->where('user_id',$uid)->column('cat_id');
            }else{
                $focus = [];
            }
//            3、判断是否已关注
            foreach($res as $k=>$v){
                if(in_array($res[$k]['id'],$focus)){
                    $res[$k]['focus'] = true;
                }else{
                    $res[$k]['focus'] = false;
                }
            }
            $list['categories'] = $res;
            return res('分类列表获取成功',1,$list);
        }
    }


    /**
     * 用户收藏或取消收藏图片
     * 1、查看收藏记录看是否已经收藏
     * 2、如果要收藏且记录不存在，则添加记录
     * 3、如果取消收藏且记录存在，则删除记录
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function putCollectedStatus()
    {
        $vld = MyValidate::makeValidate(['session','id','collected']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }

//        收藏字段默认值设为真
        $collected = $p['collected']??true;
//        查找收藏记录看是否已经收藏
        $where['user_id'] = $uid;
        $where['picture_id'] = $p['id'];
        $exist = db('collected')->where($where)->find();
//        作为最终结果的判断flag
        $res = true;
//        如果要收藏且记录不存在，则添加记录
        if($collected){
            if(!$exist){
                $insert = [
                    'user_id' => $uid,
                    'picture_id'  =>  $p['id'],
                    'create_time'  =>  time()
                ];
                db('picture')->where('id',$p['id'])->setInc('collected_count');
                $res = db('collected')->insert($insert);
            }
            return $res?res('收藏成功',1,[]):res('收藏失败');
//        如果取消收藏且记录存在，则删除记录
        }else{
            if($exist){
                db('picture')->where('id',$p['id'])->setDec('collected_count');
                $res = db('collected')->where($where)->delete();
            }
            return $res?res('取消收藏成功',1,[]):res('取消收藏失败');
        }
    }


    /**
     * 获得首页轮播图banner信息
     * 1、设置查询条件
     * 2、进行一对多查询，需要连接表查询图片拥有的标签
     * @return \think\response\Json
     */
    public function getBannerList()
    {
//        查询条件
        $where = [];
        $where['banner'] = 1;
//        $where['finished'] = 2;
//        多对多连接查询，用到group_concat将多个标签结果合并成字符串
        $banner = db('picture')
                ->alias('pic')
                ->where($where)
                ->join('picture_type pt','pic.type_id = pt.id','LEFT')
                ->join('label lb','pic.id = lb.picture_id','LEFT')
                ->field('pic.id,pic.path,pic.width,pic.height,
                        pt.id as type_id,pt.name as type_name,
                        group_concat(lb.label order by lb.count desc separator \',\') as labels')
                ->group('pic.id')
                ->page(1,6)
                ->select();
        if(!$banner){
            return res('轮播图获取失败');
        }else{
//            将字符串逐个转换成数组，变量需要使用$banner才能修改真实值
            foreach($banner as $k => $v){
                if(isset($banner[$k]['labels'])){
                    $labels = explode(',',$banner[$k]['labels']);
                }else{
                    $labels = [];
                }
//                数组截取前几个
                $banner[$k]['labels'] = array_slice($labels,0,5);
//                设置收藏字段为false
                $banner[$k]['collected'] = false;
            }
            return res('轮播图获取成功',1,$banner);
        }
    }


    /**
     * 提交一张图片的标签
     * 0、如果之前打过标签，先把相应的标签找出来，计数减1，清除计数为0的行
     * 1、检查标签是否已经存在，如果不存在则添加，存在则计数加1
     * 2、将标签添加入库，存在则自增，否则新建
     * 3、检查图片是否满足完成条件
     * 4、将添加操作记录日志中
     * @return string
     */
    public function makeLabelsToId()
    {
        $vld = MyValidate::makeValidate(['session','image_id','labels']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
        if(!is_numeric($uid)){ return res($uid); }
//        0、如果之前打过标签，先把相应的标签找出来，计数减1，清除计数为0的行，清除打标签记录
        $res = $this->deleteOldLabelsByUidPid($uid,$p['image_id']);
        if($res!==true){
            return $res;
        }
//        1、检查图片是否已经存在，打过人次计数加1
        $where = [];
        $where['id'] = $p['image_id'];
        $pictureInfo = db('picture')->where($where)->find();
        if(!$pictureInfo){
            return res('图片不存在');
        }
        db('picture')->where($where)->setInc('taged_count');
//        2、将标签添加入库，存在则自增，否则新建
        $where = [];
        $where['picture_id'] = $p['image_id'];
        $res = [];
        foreach($p['labels'] as $val){
            $where['label'] = $val;
            $exist = db('label')->where($where)->find();
            if($exist){
                $res[] = db('label')->where($where)->setInc('count')?$val.":添加成功":$val.":添加失败";
            }else{
                $insert = [
                    'picture_id'  =>  $p['image_id'],
                    'cat_id'    =>  $pictureInfo['type_id'],
                    'label'     =>  $val,
                    'count'     =>  1,
                    'create_time'   =>  time(),
                    'update_time'   =>  time()
                ];
                $res[] = db('label')->insert($insert)?$val.":添加成功":$val.":添加失败";
            }
        }
//        3、检查图片是否满足完成条件
        if($this->checkPictureFinishedById($p['image_id']) == 2){
            return res('检查图片是否满足完成条件时失败');
        }
//        4、将添加操作记录日志中
        $insert = [
            'user_id'   =>  $uid,
            'picture_id'  =>  $p['image_id'],
            'labels'     =>  implode(',',$p['labels']),
            'create_time'   =>  time(),
            'update_time'   =>  time()
        ];
        if(db('label_log')->insert($insert)){
            return res('标签添加成功',1,$res);
        }else{
            return res('标签日志添加失败',0,$res);
        }
    }

    /**
     * 清楚旧标签记录
     * 包括label表和label_log两个表中的记录
     * 先把相应的标签找出来，计数减1
     * 清除计数为0的行
     * 清除打标签记录
     * @param $uid
     * @param $pid
     * @return bool|string
     */
    public function deleteOldLabelsByUidPid($uid,$pid)
    {
//        0、如果之前打过标签，先把相应的标签找出来，计数减1，清除计数为0的行，清除打标签记录
        $where = [
            'user_id'   =>  $uid,
            'picture_id'    =>  $pid
        ];
        $labels = db('label_log')->where($where)->column('labels');
        if($labels){
            $labels = explode(',',$labels[0]);
//            旧标签的计数减一
            $where = [
                'picture_id'    =>  $pid,
                'label'=>['in',$labels]
            ];
            if(!db('label')->where($where)->setDec('count')){
                return res('旧标签清除失败');
            }
//            清除计数为0的行
            $where = [
                'count' =>  ['<=',0]
            ];
            db('label')->where($where)->delete();
//            清楚label_log中记录
            $where = [
                'user_id'   =>  $uid,
                'picture_id'    =>  $pid
            ];
            if(!db('label_log')->where($where)->delete()){
                return res('旧标签日志清楚失败');
            }
        }
        return true;
    }

    /**
     * 判断标签是否满足已完成条件
     * 判断算法如下：
     * 1、先从该图片的所有标签中选出前$count个，得到他们的count值数组$countArray
     * 2、计算得到count的总和$sum，判断$sum<$finished_sum，小于则false
     * 3、再计算出count的平均值$avg，判断$countArray数组中第$check_idx个的值是否小于平均值，小于则false
     * 4、否则就满足条件，进行相应的表更新操作
     * 返回值：0是不符合，1是符合或者操作成功，2是符合但表更新失败
     * @param $id
     * @return int
     */
    public function checkPictureFinishedById($id)
    {
//        $id = input('post.id');
//        1、先检查是否已经标记为已完成
        $pictureInfo = db('picture')->where('id',$id)->find();
        if($pictureInfo['finished']){
            return 1;
        }
//        2、配置finished条件值
        $count = config('ACCEPTED_COUNT');
        $finished_sum = config('ACCEPTED_SUM');
        $check_idx  = config('ACCEPTED_CHECK_IDX');
//        3、找出该图片的标签记录，进行判断
        $countArray = db('label')
            ->where('picture_id',$id)
            ->order('count desc')
            ->page(1,$count)
            ->column('count');
//        如果前几个标签的总count不满足则false
        $sum = 0;
        foreach($countArray as $v){
            $sum += $v;
        }
        if($sum < $finished_sum){
            return 0;
        }
//        如果第N个标签的count小于平均值则false
        $avg = round($sum/$count);
        if($countArray[$check_idx] < $avg){
            return 0;
        }
//        4、满足条件，进行标签接受操作,更新label表和picture表
        $id_array = db('label')
            ->where('picture_id',$id)
            ->order('count desc')
            ->page(1,$count)
            ->column('id');
        $where = [
            'picture_id'    =>  $id,
            'id'    =>  ['in',$id_array],
        ];
//        更新label表中标签为接受
        $update = [
            'accepted'  =>  1,
            'update_time'   =>  time()
        ];
        $label_res = db('label')->where($where)->update($update);
//        更新picture表中该图片为已完成
        $update = [
            'finished'  =>  1,
            'finished_time' =>  time(),
            'update_time'   =>  time()
        ];
        $pic_res = db('picture')->where('id',$id)->update($update);
        return $label_res&&$pic_res?1:2;
    }


    /**
     * 根据类型获取图片（待完善）
     * 1、用户存在则进行定向推送
     * 2、获取相关图片
     * 3、将字符串逐个转换成数组，变量需要使用$images才能修改真实值
     * @return string
     */
    public function getImageByType()
    {
        $vld = MyValidate::makeValidate(['type_id','page','count']);
        if($vld!==true){ return res($vld); }
        $p = input('post.');
        $redis = new Redis();
        if(isset($p['session'])){
            $uid = MyValidate::checkSessionExistBySession($redis,$p['session']);
//        if(!is_numeric($uid)){ return res($uid); }
        }else{
            $uid = false;
        }

//       数据加工
        $type = $p['type']??1;
        $page = $p['page']??1;
        $count = $p['count']??10;
//        只推送当前任务的
        $task = db('task')->where(['status'=>1,'finished'=>0])->order('create_time asc')->find();
        $where = [];
        $where['task_id'] = $task['id'];
//       1、用户存在则进行定向推送
        if($uid){
            $where['finished'] = ['<>',1];
            $where['type_id'] = $type;
//           查询用户收藏过的所有图片ID
            $collected = db('collected')->where('user_id',$uid)->column('picture_id');
//       否则随机推送
        }else{
            $where['finished'] = ['<>',1];
            $collected = [];
        }
        return $this->getImageByWhere($where,$page,$count,$collected);
    }

    /**
     * 用来根据where条件获取图片
     * @param array $where
     * @param int $page
     * @param int $count
     * @param array $collected
     * @return string
     */
    public function getImageByWhere($where=[],$page=1,$count=4,$collected=[])
    {
//        2、获取相关图片
        $images = db('picture')
            ->alias('pic')
            ->where($where)
            ->join('picture_type pt','pic.type_id = pt.id','LEFT')
            ->join('label lb','pic.id = lb.picture_id','LEFT')
            ->field('pic.id,path,width,height,
            pt.id as type_id,pt.name as type_name,
            group_concat(lb.label order by lb.count desc separator \',\') as labels')
            ->group('pic.id')
            ->page($page,$count)
            ->select();
        if($images){
//            3、将字符串逐个转换成数组，变量需要使用$images才能修改真实值
            foreach($images as $k => $v){
                if(isset($images[$k]['labels'])){
                    $labels = explode(',',$images[$k]['labels']);
                }else{
                    $labels = [];
                }
//              数组截取前几个
                $images[$k]['labels'] = array_slice($labels,0,config('ACCEPTED_COUNT'));
//              判断是否在收藏列表里
                $images[$k]['collected'] = in_array($images[$k]['id'],$collected)?true:false;
            }
            $data['images'] = $images;
            return res('推送成功',1,$data);
        }else{
            return res('推送失败');
        }
    }


    /**
     * * 上传一个任务中的图片
     * 1、校验数据
     * 2、上传、压缩图片，生成缩略图
     * 3、图片数据入库
     * @return string
     */
    public function uploadImages()
    {
//      1、校验数据
        $aid = MyValidate::checkAdminExistByCookie();
        if(!is_numeric($aid)){ return res($aid); }
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
                $img->save($save_path,null,85);
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
                    $img_thumb->thumb(250,250)->save($save_thumb_path,null,95);
                }
//                3、图片数据入库
                $data = [
                    'name'  =>  $info->getFilename(),
                    'width' =>  $img->width(),
                    'height'=>  $img->height(),
                    'task_id'   =>  0,
                    'type_id'   =>  1,
                    'path' =>  $info->getSaveName(),
                    'finished'  =>  0,
                    'taged_count'  =>  0,
                    'create_time'   =>  time(),
                    'update_time'   =>  time()
                ];
                $pid = db('picture')->insertGetId($data);
                if(!$pid){
                    return res('图片信息录入失败');
                }
                $status[] = [
                    'id'    =>  $pid,
                    'path'  =>  $data['path']
                ];
            }else{
                $status[] = 'failed';
            }
        }
        return res('上传完成',1,$status);
    }
}