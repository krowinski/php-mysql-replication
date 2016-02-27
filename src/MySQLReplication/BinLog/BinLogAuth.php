<?php

namespace MySQLReplication\BinLog;

use MySQLReplication\Exception\BinLogException;

/**
 * Class PackAuth
 */
class BinLogAuth
{
    /**
     * 2^24 - 1 16m
     * @var int
     */
    public $packageMaxLength = 16777215;

    /**
     * http://dev.mysql.com/doc/internals/en/auth-phase-fast-path.html 00 FE
     * @var array
     */
    public $packageOkHeader = [0, 254];

    /**
     * FF
     * @var array
     */
    public $packageErrorHeader = [255];

    /**
     * @param $flag
     * @param $user
     * @param $pass
     * @param $salt
     * @return string
     * @link http://dev.mysql.com/doc/internals/en/secure-password-authentication.html#packet-Authentication::Native41
     */
    public function createAuthenticationPacket($flag, $user, $pass, $salt)
    {
        $data = pack('L', $flag);
        $data .= pack('L', $this->packageMaxLength);
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

    /**
     * @param string $packet
     * @return array
     * @throws BinLogException
     */
    public function isWriteSuccessful($packet)
    {
        $head = ord($packet[0]);
        if (in_array($head, $this->packageOkHeader))
        {
            return ['status' => true, 'code' => 0, 'msg' => ''];
        }
        else
        {
            $error_code = unpack('v', $packet[1] . $packet[2])[1];
            $error_msg = '';
            for ($i = 9; $i < strlen($packet); $i++)
            {
                $error_msg .= $packet[$i];
            }

            throw new BinLogException($error_msg, $error_code);
        }
    }
}