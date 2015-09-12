<?php
class Cookies
{
  public static function get($key)
  {
    return $_COOKIE[$key];
  }

  /** expire is number of seconds from now to expire in. Default is 24hrs. **/
  public static function set($key, $value, $expire = 86400)
  {
    setcookie($key, $value, time() + $expire);
  }

  public static function delete($key)
  {
    setcookie($key, "", time()-3600);
  }
}
?>
