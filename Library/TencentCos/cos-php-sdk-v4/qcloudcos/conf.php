<?php

namespace qcloudcos;

class Conf
{
    // Cos php sdk version number.
    const VERSION = 'v4.2.3';
    const API_COSAPI_END_POINT = 'http://region.file.myqcloud.com/files/v2/';

    public static $appID = '';

    public static $secretID = '';

    public static $secretKey = '';

    /**
     * Get the User-Agent string to send to COS server.
     */
    public static function getUserAgent()
    {
        return 'cos-php-sdk-' . self::VERSION;
    }

}