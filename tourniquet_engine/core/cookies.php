<?php
class Cookies
{
  private static $test_cookies = array();
  public static $default_secure = true;

  public static function get($key)
  {
    return Config::$env == "test" ? self::$test_cookies[$key] : $_COOKIE[$key];
  }

  /** expire is number of seconds from now to expire in. Default is 24hrs. **/
  public static function set($key, $value, $expire = 86400, $page = '/', $domain = '', $secure = null, $httponly = false)
  {
    if ($secure == null) { $secure = self::$default_secure; }
    if (Config::$env == "test")
      self::$test_cookies[$key] = $value;
    else
      setcookie($key, $value, time() + $expire, $page, $domain, $secure, $httponly);
  }

  public static function delete($key)
  {
    if (Config::$env == "test")
      unset(self::$test_cookies[$key]);
    else
      setcookie($key, "", time()-3600);
  }
}
?>
