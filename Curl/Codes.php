<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Curl;

class Codes
{
    /**
     * @var array
     */
    private static $connectionErrors = array(
        CURLE_OPERATION_TIMEOUTED => true,
        CURLE_COULDNT_RESOLVE_HOST => true,
        CURLE_COULDNT_RESOLVE_PROXY => true,
        CURLE_COULDNT_CONNECT => true,
        CURLE_SSL_CONNECT_ERROR => true,
        CURLE_GOT_NOTHING => true,
    );

    /**
     * @param int $errorCode
     * @return bool
     */
    public static function isConnectionError($errorCode)
    {
        return isset(self::$connectionErrors[$errorCode]);
    }
}
 