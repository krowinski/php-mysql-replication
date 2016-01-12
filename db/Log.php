<?php
/**
 * Created by PhpStorm.
 * User: zhaozhiqiang
 * Date: 16/1/6
 * Time: 下午5:05
 */
class Log {


	public static function out($message, $category = 'out') {
		$file = Config::$OUT;
        return self::_write($message, $category, $file);
	}
    public static function error($message, $category, $file) {
        return self::_write($message, $category, $file);
    }

    public static function warn($message, $category, $file ) {
        return self::_write($message, $category, $file);
    }

    public static function notice($message, $category, $file ) {
        return self::_write($message, $category, $file);
	}


	private static function _write($message, $category, $file) {
		return	file_put_contents(
            $file,
            $category . '|' . date('Y-m-d H:i:s') . '|'. $message . "\n",
            FILE_APPEND
        );

	}
}
