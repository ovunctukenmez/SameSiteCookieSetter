<?php
/**
 * @author Ovunc Tukenmez <ovunct@live.com>
 * @version 1.1.0
 * Date: 28.03.2020
 *
 * This class adds samesite parameter for cookies created by session_start function.
 * The browser agent is also checked against incompatible list of browsers.
 * setcookie signature which comes with php 7.3.0 is used (even if the server's php version is lower)
 */

class SameSiteCookieSetter
{
    static private $_is_browser_compatible = array();

    /*
     * sets cookie
     * setcookie ( string $name [, string $value = "" [, array $options = [] ]] ) : bool
     * setcookie signature which comes with php 7.3.0
     * supported $option keys: expires, path, domain, secure, httponly and samesite
     * possible $option[samesite] values: None, Lax or Strict
     */
    public static function setcookie($name, $value="", $options = array())
    {
        $same_site = isset($options['samesite']) ? $options['samesite'] : '';
        $is_secure = isset($options['secure']) ? boolval($options['secure']) : false;
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        if (version_compare('7.3.0', phpversion()) == 1) {
            unset($options['samesite']);
            unset($options['secure']);

            $expires = isset($options['expires']) ? $options['expires'] : 0;
            $path = isset($options['path']) ? $options['path'] : '';
            $domain = isset($options['domain']) ? $options['domain'] : '';
            $is_httponly = isset($options['httponly']) ? boolval($options['httponly']) : false;

            $result = setcookie($name,$value,$expires,$path,$domain);

            if (self::isBrowserSameSiteCompatible($user_agent)) {
                $new_headers = array();
                $headers_list = array_reverse(headers_list());
                $is_modified = false;
                foreach ($headers_list as $_header) {
                    if (!$is_modified && strpos($_header, 'Set-Cookie: ' . $name) === 0) {
                        $additional_labels = array();

                        $is_secure = ($same_site == 'None' ? true : $is_secure);

                        $new_label = '; HttpOnly';
                        if ($is_httponly && strpos($_header,$new_label) === false){
                            $additional_labels[] = $new_label;
                        }

                        $new_label = '; Secure';
                        if ($is_secure && strpos($_header,$new_label) === false){
                            $additional_labels[] = $new_label;
                        }

                        $new_label = '; SameSite=' . $same_site;
                        if (strpos($_header,$new_label) === false){
                            $additional_labels[] = $new_label;
                        }

                        $_header = $_header . implode('',$additional_labels);
                        $is_modified = true;
                    }
                    $new_headers[] = $_header;
                }

                header_remove();
                $new_headers = array_reverse($new_headers);
                foreach ($new_headers as $_header){
                    header($_header,false);
                }
            }
        } else {
            if (self::isBrowserSameSiteCompatible($user_agent) == false) {
                $same_site = '';
            }
            $is_secure = ($same_site == 'None' ? true : $is_secure);

            $options['samesite'] = $same_site;
            $options['secure'] = $is_secure;

            $result = setcookie($name, $value, $options);
        }

        return $result;
    }

    private static function _setIsBrowserCompatible($user_agent_key,$value){
        self::$_is_browser_compatible[$user_agent_key] = $value;
    }
    private static function _getIsBrowserCompatible($user_agent_key){
        if (isset(self::$_is_browser_compatible[$user_agent_key])){
            return self::$_is_browser_compatible[$user_agent_key];
        }
        return null;
    }

    public static function isBrowserSameSiteCompatible($user_agent)
    {
        $user_agent_key = md5($user_agent);
        $self_check = self::_getIsBrowserCompatible($user_agent_key);
        if ($self_check !== null){
            return $self_check;
        }

        // check Chrome
        $regex = '#(CriOS|Chrome)/([0-9]*)#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version = $matches[2];
            if ($version < 67) {
                self::_setIsBrowserCompatible($user_agent_key,false);
                return false;
            }
        }

        // check iOS
        $regex = '#iP.+; CPU .*OS (\d+)_\d#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version = $matches[1];
            if ($version < 13) {
                self::_setIsBrowserCompatible($user_agent_key,false);
                return false;
            }
        }

        // check MacOS 10.14
        $regex = '#Macintosh;.*Mac OS X (\d+)_(\d+)_.*AppleWebKit#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version_major = $matches[1];
            $version_minor = $matches[2];
            if ($version_major == 10 && $version_minor == 14) {
                // check Safari
                $regex = '#Version\/.* Safari\/#';
                if (preg_match($regex, $user_agent) == true) {
                    self::_setIsBrowserCompatible($user_agent_key,false);
                    return false;
                }
                // check Embedded Browser
                $regex = '#AppleWebKit\/[\.\d]+ \(KHTML, like Gecko\)#';
                if (preg_match($regex, $user_agent) == true) {
                    self::_setIsBrowserCompatible($user_agent_key,false);
                    return false;
                }
            }
        }

        // check UC Browser
        $regex = '#UCBrowser/(\d+)\.(\d+)\.(\d+)#';
        if (preg_match($regex, $user_agent, $matches) == true) {
            $version_major = $matches[1];
            $version_minor = $matches[2];
            $version_build = $matches[3];
            if ($version_major == 12 && $version_minor == 13 && $version_build == 2) {
                self::_setIsBrowserCompatible($user_agent_key,false);
                return false;
            }
        }

        self::_setIsBrowserCompatible($user_agent_key,true);
        return true;
    }
}
