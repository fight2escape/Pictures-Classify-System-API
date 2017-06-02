<?php
namespace app\index\controller;
use think\Controller;
use think\Validate;
use app\index\model\User;

class Image extends Controller
{
    /**
     * 获取分类展示列表
     * 1、查表获取返回
     * @return \think\response\Json
     */
    public function getCategoryList(){
        $res = db('image_type')
            ->alias('it')
            ->join('image img','it.image_id = img.id')
            ->field('it.id,it.name,img.path')
            ->select();
        if(!$res){
            return show(0,[],'分类列表获取失败');
        }else{
            $list['categories'] = $res;
            return show(1,$list,'分类列表获取成功');
        }
    }


    /**
     * 用户收藏或取消收藏图片
     * 1、校验字段
     * 2、查看收藏记录看是否已经收藏
     * 3、如果要收藏且记录不存在，则添加记录
     * 4、如果取消收藏且记录存在，则删除记录
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function putCollectedStatus()
    {
        // 校验字段
        $p = input('post.');
        $rule = [
            'session'   =>  'require|length:32',
            'id'        =>  'require|number',
            'collected' =>  'boolean'
        ];
        $this->validating($rule,$p);
        // 收藏字段默认值设为真
        $collected = $p['collected']??true;
        // 判断用户是否存在
        $user = db('user')->where('session',$p['session'])->find();
        if(!$user){
            return show(0,[],'用户不存在');
        }
        // 查找收藏记录看是否已经收藏
        $where['user_id'] = $user['id'];
        $where['image_id'] = $p['id'];
        $exist = db('collected')->where($where)->find();
        // 作为最终结果的判断flag
        $res = true;
        // 如果要收藏且记录不存在，则添加记录
        if($collected){
            if(!$exist){
                $insert = [
                    'user_id' => $user['id'],
                    'image_id'  =>  $p['id'],
                    'create_time'  =>  time()
                ];
                $res = db('collected')->insert($insert);
            }
            return $res?show(1,[],'收藏成功'):show(0,[],'收藏失败');
        // 如果取消收藏且记录存在，则删除记录
        }else{
            if($exist){
                $res = db('collected')->where($where)->delete();
            }
            return $res?show(1,[],'收藏取消成功'):show(0,[],'收藏取消失败');
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
        // 查询条件
        $where = [];
        $where['banner'] = 1;
        $where['status'] = 2;
        // 一对多连接查询，用到group_concat将多个标签结果合并成字符串
        $banner = db('image')
                ->alias('img')
                ->where($where)
                ->join('image_type it','img.type_id = it.id','LEFT')
                ->join('label lb','img.id = lb.image_id','LEFT')
                ->field('img.id,img.path,
                        it.id as type_id,
                        it.name as type_name,
                        group_concat(lb.label order by lb.count desc separator \',\') as labels')
                ->group('img.id')
                ->limit(0,6)
                ->select();
        if(!$banner){
            return show(0,[],'轮播图获取失败');
        }else{
            // 将字符串逐个转换成数组，变量需要使用$banner才能修改真实值
            foreach($banner as $k => $v){
                if(isset($banner[$k]['labels'])){
                    $labels = explode(',',$banner[$k]['labels']);
                }else{
                    $labels = [];
                }
                // 数组截取前几个
                $banner[$k]['labels'] = array_slice($labels,0,5);
                // 设置收藏字段为false
                $banner[$k]['collected'] = false;
            }
            return show(1,$banner,'轮播图获取成功');
        }
    }


    /**
     * 提交一张图片的标签
     * 1、校验数据，检查用户
     * 2、将标签添加入库，存在则自增，否则新建
     * 3、将添加操作记录日志中
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function makeLabelsToId()
    {
        // 校验数据
        $p = input('post.');
        $rule = [
            'session'   =>  'require|length:32',
            'image_id'  =>  'require|number',
            'labels'    =>  'require'
        ];
        $this->validating($rule,$p);
        $user = db('user')->where('session',$p['session'])->find();
        if(!$user){
            return show(0,[],'用户不存在');
        }
        // 检查标签是否已经存在，如果不存在则添加，存在则计数加1
        $where = [];
        $where['image_id'] = $p['image_id'];
        // 添加结果
        $res = [];
        foreach($p['labels'] as $val){
            $where['label'] = $val;
            $exist = db('label')->where($where)->find();
            if($exist){
                $res[] = db('label')->where($where)->setInc('count')?$val.":添加成功":$val.":添加失败";
            }else{
                $insert = [
                    'image_id'  =>  $p['image_id'],
                    'label'     =>  $val,
                    'count'     =>  1,
                    'create_time'   =>  time(),
                    'update_time'   =>  time()
                ];
                $res[] = db('label')->insert($insert)?$val.":添加成功":$val.":添加失败";
            }
        }
        // 用户打标签日志
        $insert = [
            'user_id'   =>  $user['id'],
            'image_id'  =>  $p['image_id'],
            'labels'     =>  implode(',',$p['labels']),
            'create_time'   =>  time(),
            'update_time'   =>  time()
        ];
        if(db('label_log')->insert($insert)){
            return show(1,$res,'标签添加成功');
        }else{
            return show(0,$res,'标签添加失败');
        }
    }



    /**
     * 根据类型获取图片（待完善）
     * 参数为空则随意推送
     * 否则根据用户的兴趣点进行推荐
     * @return \think\response\Json
     */
    public function getImageByType()
    {

        // 数据校验
        $p = input('post.');
        $rule = [
            'session'	=>	'length:32',
            'type_id'   =>  'number',
            'page'      =>  'number',
            'count'     =>  'number'
        ];
        $this->validating($rule,$p);

        // 数据加工
        $session = $p['session']??false;
        $type = $p['type']??1;
        $page = $p['page']??0;
        $count = $p['count']??4;
        // 用户存在则进行定向推送
        if($session){
            // 检查用户
            $user = db('user')->where('session',$session)->find();
            if(!$user){
                return show(0,[],'用户不存在');
            }
            $where = [];
            $where['status'] = ['<>',1];
            $where['type_id'] = $type;
            // 查询用户收藏过的所有图片ID
            $collected = db('collected')->where('user_id',$user['id'])->column('image_id');
        // 否则随机推送
        }else{
            $where = [];
            $where['status'] = ['<>',1];
            $collected = [];
        }
        $images = db('image')
            ->alias('img')
            ->where($where)
            ->join('image_type it','img.type_id = it.id','LEFT')
            ->join('label lb','img.id = lb.image_id','LEFT')
            ->field('img.id,path,it.id as type_id,it.name as type_name,
                        group_concat(lb.label order by lb.count desc separator \',\') as labels')
            ->group('img.id')
            ->limit($page*$count,$count)
            ->select();
        if($images){
            // 将字符串逐个转换成数组，变量需要使用$banner才能修改真实值
            foreach($images as $k => $v){
                if(isset($images[$k]['labels'])){
                    $labels = explode(',',$images[$k]['labels']);
                }else{
                    $labels = [];
                }
                // 数组截取前几个
                $images[$k]['labels'] = array_slice($labels,0,5);
                // 判断是否在收藏列表里
                $images[$k]['collected'] = in_array($images[$k]['id'],$collected)?true:false;
            }
            $data['images'] = $images;
            return show(1,$data,'推送成功');
        }else{
            return show(0,[],'推送失败');
        }
    }

    /**
     * 图片上传接口，仅做测试用
     * @return \think\response\Json
     */
    public function uploadImages()
    {
        // 数据校验
        $p = input('post.');
        $validate = new Validate([
            'session'	=>	'require'
        ]);
        if(!$validate->check($p)) {
            return show(0, [], $validate->getError());
        }
        // 检查用户
        $user = db('user')->where('session',$p['session'])->find();
        if(!$user){
            return show(0,[],'用户不存在');
        }
        // 接收头像信息
        $image = request()->file();
        if(!$image){
            return show(0,[],'未接收到图片文件');
        }
        // 上传失败记录
        $status = [];
        foreach($image as $img){
            $info = $img->move(ROOT_PATH . 'public' . DS . 'uploads');
            if(!$info){
                $status[] = $info->getError();
            }else{
                $path = $info->getSaveName();
                $insert = [
                    'path'	=>	$path,
                    'create_time'   =>  time(),
                    'update_time'	=>	time()
                ];
                $res = db('image')->insert($insert);
                if(!$res){
                    $status[] = '数据入库失败';
                }else{
                    $status[] = '图片上传成功';
                }
            }
        }
        return show(1,[],$status);
    }

    /**
     * 验证器
     * @param $rule 验证规则
     * @param $post 待验证数据数组
     * @return \think\response\Json
     */
    public function validating($rule,$post)
    {
        // 数据校验
//         $post = input('post.');
        $validate = new Validate($rule);
        if(!$validate->check($post)) {
            return show(0, [], $validate->getError());
        }
    }


}