<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


/**
 * 获取待标记图片的完整路径（缩略图）
 * @param $imagePath
 * @return string
 */
function getImagesThumbFullPath($imagePath){
    return config('IMAGES_THUMB_PATH').DS.$imagePath;
}


/**
 * 获取待标记图片的完整路径
 * @param $imagePath
 * @return string
 */
function getImagesFullPath($imagePath){
    return config('IMAGES_PATH').DS.$imagePath;
}

/**
 * 获取头像完整路径
 * @param $avatarPath
 * @return string
 */
function getAvatarFullPath($avatarPath){
    return config('AVATAR_PATH').DS.$avatarPath;
}

/**
 * 生成加密后的密码
 * @param $pwd
 * @return string
 */
function getPassword($pwd){
    return md5(md5(md5($pwd).md5(config('SALT'))).config('PWD_SALT'));
}

/**
 * 生成新session
 * @param $username
 * @return string
 */
function getNewSession($username){
    return md5(md5($username.time()).config('SALT'));
}

/**
 * 统一返回格式
 * @param $status
 * @param array $data
 * @param string $msg
 * @return string
 */
function res($msg='', $status=0, $data=[]){
    $res = [
        'message'   =>  $msg,
        'status'    =>  $status,
        'data'      =>  $data,
    ];
    return json_encode($res);
}