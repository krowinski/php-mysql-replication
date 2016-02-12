<?php

/**
 * Class Log
 */
class Log
{
    /**
     * @param $message
     * @param string $category
     * @return int
     */
    public static function out($message, $category = 'out')
    {
        $file = Config::$OUT;
        return self::_write($message, $category, $file);
    }

    /**
     * @param $message
     * @param $category
     * @param $file
     * @return int
     */
    private static function _write($message, $category, $file)
    {
        return file_put_contents(
            $file,
            $category . '|' . date('Y-m-d H:i:s') . '|' . $message . "\n",
            FILE_APPEND
        );
    }

    /**
     * @param $message
     * @param $category
     * @param $file
     * @return int
     */
    public static function error($message, $category, $file)
    {
        return self::_write($message, $category, $file);
    }

    /**
     * @param $message
     * @param $category
     * @param $file
     * @return int
     */
    public static function warn($message, $category, $file)
    {
        return self::_write($message, $category, $file);
    }

    /**
     * @param $message
     * @param $category
     * @param $file
     * @return int
     */
    public static function notice($message, $category, $file)
    {
        return self::_write($message, $category, $file);
    }
}
