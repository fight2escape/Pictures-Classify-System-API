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
 * 统一返回格式
 * @param $status
 * @param array $data
 * @param string $msg
 * @return string
 */
function res($msg='', $status=0, $data=[])
{
    $res = [
        'message' => $msg,
        'status' => $status,
        'data' => $data,
    ];
    return json_encode($res);
}

/**
 * 生成加密后的密码
 * @param $pwd
 * @return string 加密后的字符串
 */
function getPassword($pwd){
    return md5(md5(md5($pwd).md5(config('SALT'))).config('PWD_SALT'));
}

/**
 * session等随机字符串加密
 * @param $string
 * @return string
 */
function encrypt($string){
	return md5(sha1(crypt($string,config('ENCRYPT_SALT').time()).rand()));
}