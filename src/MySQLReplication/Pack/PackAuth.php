<?php

namespace MySQLReplication\Pack;

use MySQLReplication\Definitions\ConstAuth;
use MySQLReplication\Exception\BinLogException;

/**
 * Class PackAuth
 */
class PackAuth
{
    /**
     * @param $flag
     * @param $user
     * @param $pass
     * @param $salt
     * @param string $db
     * @return string
     * @link http://dev.mysql.com/doc/internals/en/secure-password-authentication.html#packet-Authentication::Native41
     */
    public static function initPack($flag, $user, $pass, $salt, $db = '')
    {
        $data = pack('L', $flag);
        $data .= pack('L', ConstAuth::$PACK_MAX_LENGTH);
        $data .= chr(33);
        for ($i = 0; $i < 23; $i++)
        {
            $data .= chr(0);
        }
        $result = sha1($pass, true) ^ sha1($salt . sha1(sha1($pass, true), true), true);

        $data = $data . $user . chr(0) . chr(strlen($result)) . $result;
        if ($db)
        {
            $data .= $db . chr(0);
        }
        $str = pack('L', strlen($data));
        $s = $str[0] . $str[1] . $str[2];
        $data = $s . chr(1) . $data;

        return $data;
    }

    /**
     * @param $pack
     * @return array
     * @throws BinLogException
     */
    public static function success($pack)
    {
        $head = ord($pack[0]);
        if (in_array($head, ConstAuth::$OK_PACK_HEAD))
        {
            return ['status' => true, 'code' => 0, 'msg' => ''];
        }
        else
        {
            $error_code = unpack('v', $pack[1] . $pack[2])[1];
            $error_msg = '';
            for ($i = 9; $i < strlen($pack); $i++)
            {
                $error_msg .= $pack[$i];
            }
            throw new BinLogException($error_msg, $error_code);
        }
    }
}