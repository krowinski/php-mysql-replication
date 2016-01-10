<?php
/**
 * Created by PhpStorm.
 * User: zhaozhiqiang
 * Date: 16/1/6
 * Time: 下午5:05
 */
class Log {

    public static function error($message, $category, $file) {
        file_put_contents(
            $file,
            $category . '|' . date('Y-m-d H:i:s') . '|'. $message . "\n",
            FILE_APPEND
        );
    }

    public static function warn($message, $category, $file ) {
        file_put_contents(
            $file,
            $category . '|' .date('Y-m-d H:i:s') .'|'. $message . "\n",
            FILE_APPEND
        );
    }

    public static function notice($message, $category, $file ) {
        file_put_contents(
            $file,
            $category . '|' .date('Y-m-d H:i:s') .'|'. $message . "\n",
            FILE_APPEND
        );
    }
}
