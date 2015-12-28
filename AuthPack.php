<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/19
 * Time: 下午2:51
 */
class AuthPack {

    /**
     * @param $flag
     * @param $user
     * @param $pass
     * @param $salt
     * @param string $db
     * @return string
     */
    public static function initPack($flag, $user, $pass, $salt, $db = '') {
        $data = pack('L',$flag);

        // max-length 4bytes，最大16M 占3bytes
        $data .= pack('L', ConstAuth::$PACK_MAX_LENGTH);


        // Charset  1byte utf8=>33
        $data .=chr(33);


        // 空 bytes23
        for($i=0;$i<23;$i++){
            $data .=chr(0);
        }

        // http://dev.mysql.com/doc/internals/en/secure-password-authentication.html#packet-Authentication::Native41
        $result = sha1($pass, true) ^ sha1($salt . sha1(sha1($pass, true), true),true);

        //转码 8是 latin1
        //$user = iconv('utf8', 'latin1', $user);

        //
        $data = $data . $user . chr(0) . chr(strlen($result)) . $result;
        if($db) {
            $data .= $db . chr(0);
        }

        $str = pack("L", strlen($data));//  V L 小端，little endian
        $s =$str[0].$str[1].$str[2];

        $data = $s . chr(1) . $data;

        return $data;
    }

    /**
     * @breif 校验数据包是否认证成功
     * @param $pack
     * @return array
     */
    public static function success($pack) {
        $head = ord($pack[0]);
        if(in_array($head, ConstAuth::$OK_PACK_HEAD)) {
            return ['status' => true, 'code' => 0, 'msg' => ''];
        } else{
            $error_code = unpack("v", $pack[1] . $pack[2])[1];
            $error_msg  = '';
            for($i = 9; $i < strlen($pack); $i ++) {
                $error_msg .= $pack[$i];
            }
            var_dump(['code' => $error_code, 'msg' => $error_msg]);
            exit;
        }

    }
}