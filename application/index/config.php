<?php
/**
 * Created by PhpStorm.
 * User: Shinelon
 * Date: 2017/5/25
 * Time: 11:13
 */

return [
//    加密盐值
	'SALT'		=>	'_We_are_the_CHAMPION__!!!$$$%%%007',
//    密码盐值
    'PWD_SALT'  =>  'zhan_wu_ZHA_ni_ming_CAN_SAI_dui@CUMT.EDU.CN',
//    session过期时间
    'SESSION_TIMEOUT'   =>  3600*1,
//    cookie过期时间
    'COOKIE_ADMIN'      =>  3600*2,
//    头像原图保存路径
    'AVATAR_PATH'   =>  ROOT_PATH . 'public' . DS . 'avatar',
//    测试图片集的保存路径
    'IMAGES_PATH'    =>  ROOT_PATH . 'public' . DS . 'images',
    'IMAGES_THUMB_PATH'    =>  ROOT_PATH . 'public' . DS . 'images_thumb',
//    标签接受策略
    'ACCEPTED_COUNT'    =>  6,
    'ACCEPTED_SUM'      =>  12,
    'ACCEPTED_CHECK_IDX'    =>  3,
];