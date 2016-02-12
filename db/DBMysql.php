<?PHP

class DBConstNamespace {
    // 数据库编码相关
    const ENCODING_GBK      = 0; ///< GBK 编码定义
    const ENCODING_UTF8     = 1; ///< UTF8 编码定义
    const ENCODING_LATIN    = 2; ///< LATIN1 编码定义
    const ENCODING_UTF8MB4  = 3; ///< UTF8MB4 编码定义, 4字节emoji表情要用,http://punchdrunker.github.io/iOSEmoji/table_html/flower.html
    // 数据库句柄需要ping 重连
    const HANDLE_PING       = 100;

    // 数据库句柄不能 重连
    const NOT_HANDLE_PING   = 200;

}



class DBMysql {

    /**
     * 已打开的db handle
     */
    private static $_HANDLE_ARRAY   = array();
    private static $_HANDLE_CONFIG  = array();


    private static function _getHandleKey($params) {
        ksort($params);
        return md5(implode('_' , $params));
    }


    /// 根据数据库表述的参数获取数据库操作句柄
    /// @param[in] array $db_config_array, 是一个array类型的数据结构，必须有host, username, password 三个熟悉, port为可选属性， 缺省值分别为3306
    /// @param[in] string $db_name, 数据库名称
    /// @param[in] enum $encoding, 从$DBConstNamespace中数据库编码相关的常量定义获取, 有缺省值 $DBConstNamespace::ENCODING_UTF8
    /// @return 非FALSE表示成功获取hadnle， 否则返回FALSE
    public static function createDBHandle($db_config_array, $db_name, $encoding = DBConstNamespace::ENCODING_UTF8) {
        $db_config_array['db_name']     = $db_name;
        $db_config_array['encoding']    = $encoding;


        self::$_HANDLE_CONFIG = $db_config_array;

        $handle_key = self::_getHandleKey($db_config_array);

        $port = 3306;
        do {
            if (!is_array($db_config_array))
                break;
            if (!is_string($db_name))
                break;
            if (strlen($db_name) == 0)
                break;
            if (!array_key_exists('host', $db_config_array))
                break;
            if (!array_key_exists('username', $db_config_array))
                break;
            if (!array_key_exists('password', $db_config_array))
                break;
            if (array_key_exists('port', $db_config_array)) {
                $port = (int)($db_config_array['port']);
                if (($port < 1024) || ($port > 65535))
                    break;
            }
            $host = $db_config_array['host'];
            if (strlen($host) == 0)
                break;
            $username = $db_config_array['username'];
            if (strlen($username) == 0)
                break;
            $password = $db_config_array['password'];
            if (strlen($password) == 0)
                break;

            $handle = @mysqli_connect($host, $username, $password, $db_name, $port);
            // 如果连接失败，再重试2次
            for ($i = 1; ($i < 3) && (FALSE === $handle); $i++) {
                // 重试前需要sleep 50毫秒
                usleep(50000);
                $handle = @mysqli_connect($host, $username, $password, $db_name, $port);
            }
            if (FALSE === $handle)
                break;

            if (FALSE === mysqli_set_charset($handle, "utf8")) {
                self::logError( sprintf("Connect Set Charset Failed2:%s", mysqli_error($handle)), 'mysqlns.connect');
                mysqli_close($handle);
                break;
            }


            self::$_HANDLE_ARRAY[$handle_key]    = $handle;

            return $handle;
        } while (FALSE);

        // to_do, 连接失败
        self::logError( sprintf("Connect failed:time=%s", date('Y-m-d H:i:s',time())), 'mysqlns.connect');
        return FALSE;
    }

    /// 释放通过getDBHandle或者getDBHandleByName 返回的句柄资源
    /// @param[in] handle $handle, 你懂的
    /// @return void
    public static function releaseDBHandle($handle) {
        if (!self::_checkHandle($handle))
            return;
        foreach (self::$_HANDLE_ARRAY as $handle_key => $handleObj) {
            if ($handleObj->thread_id == $handle->thread_id) {
                unset(self::$_HANDLE_ARRAY[$handle_key]);
            }
        }
        mysqli_close($handle);
    }

    /// 将所有结果存入数组返回
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @param[in] string $sql, 具体执行的sql语句
    /// @return FALSE表示执行失败， 否则返回执行的结果, 结果格式为一个数组，数组中每个元素都是mysqli_fetch_assoc的一条结果
    public static function query($handle, $sql) {
        do {
            if (($result = self::mysqliQueryApi($handle, $sql)) === FALSE){
                break;
            }
            if ($result === true) {
                self::logWarn("err.func.query,SQL=$sql", 'mysqlns.query' );
                return array();
            }
            $res = array();
            while($row = mysqli_fetch_assoc($result)) {
                $res[] = $row;
            }
            mysqli_free_result($result);
            return $res;
        } while (FALSE);
        // to_do, execute sql语句失败， 需要记log
        self::logError( "SQL Error: $sql, errno=" . self::getLastError($handle), 'mysqlns.sql');

        return FALSE;
    }

    /// 将查询的第一条结果返回
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @param[in] string $sql, 具体执行的sql语句
    /// @return FALSE表示执行失败， 否则返回执行的结果, 执行结果就是mysqli_fetch_assoc的结果
    public static function queryFirst($handle, $sql) {
        if (!self::_checkHandle($handle))
            return FALSE;
        do {
            if (($result = self::mysqliQueryApi($handle, $sql)) === FALSE)
                break;
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return $row;
        } while (FALSE);
        // to_do, execute sql语句失败， 需要记log
        self::logError( "SQL Error: $sql," . self::getLastError($handle), 'mysqlns.sql');
        return FALSE;
    }

    /**
     * 将所有结果存入数组返回
     * @param Mysqli $handle 句柄
     * @param string $sql 查询语句
     * @return FALSE表示执行失败， 否则返回执行的结果, 结果格式为一个数组，数组中每个元素都是mysqli_fetch_assoc的一条结果
     */
    public static function getAll($handle , $sql) {
        return self::query($handle, $sql);
    }

    /**
     * 将查询的第一条结果返回
     * @param[in] Mysqli $handle, 操作数据库的句柄
     * @param[in] string $sql, 具体执行的sql语句
     * @return FALSE表示执行失败， 否则返回执行的结果, 执行结果就是mysqli_fetch_assoc的结果
     */
    public static function getRow($handle , $sql) {
        return self::queryFirst($handle, $sql);
    }

    /**
     * 查询第一条结果的第一列
     * @param Mysqli $handle, 操作数据库的句柄
     * @param string $sql, 具体执行的sql语句
     */
    public static function getOne($handle , $sql) {
        $row    = self::getRow($handle, $sql);
        if (is_array($row))
            return current($row);
        return $row;
    }

    /// 得到最近一次操作影响的行数
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @return FALSE表示执行失败， 否则返回影响的行数
    public static function lastAffected($handle) {
        if (!is_object($handle))
            return FALSE;
        $affected_rows = mysqli_affected_rows($handle);
        if ($affected_rows < 0)
            return FALSE;
        return $affected_rows;
    }

    /*
     *  返回最后一次查询自动生成并使用的id
     *  @param[in] handle $handle, 操作数据库的句柄
     *  @return FALSE表示执行失败， 否则id
     */
    public static function getLastInsertId($handle) {
        if (!is_object($handle)) {
            return false ;
        }
        if (($lastInsertId = mysqli_insert_id($handle)) <= 0) {
            return false ;
        }
        return $lastInsertId;
    }

    /// 得到最近一次操作错误的信息
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @return FALSE表示执行失败， 否则返回 'errorno: errormessage'
    public static function getLastError($handle) {
        if(($handle)) {
            return mysqli_errno($handle).': '.mysqli_error($handle);
        }
        return FALSE;
    }

    /**
     * @brief 检查handle
     * @param[in] handle $handle, 操作数据库的句柄
     * @return boolean true|成功, false|失败
     */
    private static function _checkHandle($handle, $log_category = 'mysqlns.handle') {
        if (!is_object($handle) || $handle->thread_id < 1) {
            if ($log_category) {
                self::logError(sprintf("handle Error: handle='%s'",var_export($handle, true)), $log_category);
            }
            return false;
        }
        return true;
    }


    public static function mysqliQueryApi($handle, $sql) {
        do {
            $result = mysqli_query($handle, $sql);

            return $result;
        } while (0);
        return false;
    }

    /**
     * @breif 记录统一错误日志
     */
    protected static function logError($message, $category) {
        Log::error( $message, $category , Config::$LOG['mysql']['error']);
    }

    /**
     * @breif 记录统一警告日志
     */
    protected static function logWarn($message, $category) {

        Log::warn( $message, $category , Config::$LOG['mysql']['warn']);

    }
}
