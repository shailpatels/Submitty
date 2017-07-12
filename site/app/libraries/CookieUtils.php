<?php

namespace app\libraries;


/**
 * Class CookieUtils
 *
 * We don't bother unit testing this class as it'd basically just be tests around PHP functions
 * and globals which we can expect to work. Perhaps frowned upon, but not worth worrying
 * about for testing for the sake of testing.
 *
 * @codeCoverageIgnore
 */
class CookieUtils {
    /**
     * Wrapper around the PHP function setcookie that deals with figuring out if we should be setting this cookie
     * such that it should only be accessed via HTTPS (secure) as well as allow easily passing an array to set as
     * the cookie data.
     *
     * @param string        $name name of the cookie
     * @param string|array  $data data of the cookie, if array, will json_encode it
     * @param int           $expire when should the cookie expire
     *
     * @return bool true if successfully able to set the cookie, else false
     */
    public static function setCookie($name, $data, $expire=0) {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
        return setcookie($name, $data, $expire, "/", "", $secure);
    }

    /**
     * Expires a cookie by setting its expire date to a time somewhere in the past.
     *
     * @param string $name name of the cookie
     *
     * @return bool true if cookie was successfuly set, else false
     */
    public static function expireCookie($name) {
        return static::setCookie($name, '', time() - 3600);
    }
}
