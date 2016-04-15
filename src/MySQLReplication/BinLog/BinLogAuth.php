<?php

namespace MySQLReplication\BinLog;

/**
 * Class BinLogAuth
 * @package MySQLReplication\BinLog
 */
class BinLogAuth
{
    /**
     * 2^24 - 1 16m
     * @var int
     */
    private $binaryDataMaxLength = 16777215;

    /**
     * @param $flag
     * @param $user
     * @param $pass
     * @param $salt
     * @return string
     * @link http://dev.mysql.com/doc/internals/en/secure-password-authentication.html#packet-Authentication::Native41
     */
    public function createAuthenticationBinary($flag, $user, $pass, $salt)
    {
        $data = pack('L', $flag);
        $data .= pack('L', $this->binaryDataMaxLength);
        $data .= chr(33);
        for ($i = 0; $i < 23; $i++)
        {
            $data .= chr(0);
        }
        $result = sha1($pass, true) ^ sha1($salt . sha1(sha1($pass, true), true), true);

        $data = $data . $user . chr(0) . chr(strlen($result)) . $result;
        $str = pack('L', strlen($data));
        $s = $str[0] . $str[1] . $str[2];
        $data = $s . chr(1) . $data;

        return $data;
    }
}