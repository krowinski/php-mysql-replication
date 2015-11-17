<?PHP
class TimeDate {

    const SECONDS_PER_DAY = 86400;

    /**
     * 获取当前时间的微秒数
     * @return FALSE表示获取失败， 否则返回长整型整数
     */
    public static function getMicrosecond() {
        list($usec, $sec) = explode(" ", microtime());
        return $sec*1000000 + (int)($usec * 1000000);
    }

	/**
	 * 格式化日期
	 */
	public static function formatDate($dateformat, $timestamp='', $format=0, $noSecond = false) {

		$_timestamp 	= idate('U');
		$_lang_hour 	= '小时';
		$_lang_minute 	= '分钟';
		$_lang_second 	= '秒钟';
		$_lang_now 		= '现在';

		if(empty($timestamp)) {
			$timestamp = $_timestamp;
		}

		$result = '';
		if($format) {
			$time = $_timestamp - $timestamp;
			if($time > 24*3600) {
				$result = date($dateformat, $timestamp);
			} elseif ($time >= 3600) {
				$result = intval($time/3600) . $_lang_hour;
                if($noSecond){
                    $result .= '前';
                }
			} elseif ($time >= 60) {
				$result = intval($time/60) . $_lang_minute;
                if($noSecond){
                    $result .= '前';
                }
			} elseif ($time > 0) {
                if(!$noSecond){
                    $result = $time . $_lang_second;
                }else{
                    $result = '1分钟前';
                }
			} else {

				$result = $_lang_now;
			}
		} else {
			$result = date($dateformat, $timestamp);
		}
		return $result;
	}

	/**
	 * 格式化日期
	 */
	public static function formatDate2($timestamp='', $noSecond = false) {
        if(empty($timestamp)) {
            return '1分钟内';
        }

		$_timestamp 	= idate('U');
        $_lang_day      = '天';
		$_lang_hour 	= '小时';
		$_lang_minute 	= '分钟';
		$_lang_minute2 	= '分';
		$_lang_second 	= '秒钟';
		$_lang_now 		= '现在';

		$result = '';
		$time = $timestamp;
		if($time > 24*3600) {
			$result = floor($time/(24*3600)) . $_lang_day;
            if($time - floor($time/(24*3600))*24*3600 > 3600){
                $result .= floor(($time - floor($time/(24*3600))*24*3600)/3600) . $_lang_hour;
            }
		} elseif ($time >= 3600) {
			$result = floor($time/3600) . $_lang_hour;
            if($time - floor($time/3600)*3600 > 0){
                $result .= floor(($time - floor($time/3600)*3600)/60) . $_lang_minute2;
            }
            if($noSecond){
                $result .= '前';
            }
		} elseif ($time >= 60) {
			$result = intval($time/60) . $_lang_minute;
            if($noSecond){
                $result .= '前';
            }
		} elseif ($time > 0) {
            if(!$noSecond){
                $result = $time . $_lang_second;
            }else{
                $result = '1分钟前';
            }
		} else {

			$result = $_lang_now;
		}

		return $result;
	}

    /**
     * 生日转年龄 1984-02-25
     */
    public static function birthdayToAge($birthday) {
        return date('Y', time()) - current(explode('-', $birthday));
    }

    /**
     * 生成两个日期之间所有日期列表（用于显示图表）
     * @param string $startDate
     * @param string $endDate
     * @param string $format
     * @return array
     */
    public static function getDateSeries($startDate, $endDate, $format = 'Y-m-d') {

        $since = strtotime($startDate);
        $until = strtotime($endDate);
        if ($since == -1 || $until == -1 || $until < $since) return array();

        $series = array();
        $day = $since;
        while ($day <= $until) {
            $series[] = date($format, $day);
            $day += self::SECONDS_PER_DAY;
        }

        return $series;
    }

}
