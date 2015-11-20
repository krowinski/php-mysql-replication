<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/19
 * Time: 下午2:05
 */
class ConstAuth {


    // 2^24 - 1 16m
    public static $PACK_MAX_LENGTH = 16777215;

    // http://dev.mysql.com/doc/internals/en/auth-phase-fast-path.html
    // 00 FE
    public static $OK_PACK_HEAD = [0, 254];
    // FF
    public static $ERR_PACK_HEAD = [255];

}