<?PHP
class TimeDate {

    /**
     * 获取当前时间的微秒数
     * @return int
     */
    public static function getMicrosecond() {
        list($usec, $sec) = explode(" ", microtime());
        return $sec*1000000 + (int)($usec * 1000000);
    }

}
