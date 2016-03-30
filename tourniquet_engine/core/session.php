<?php
class Session
{
  private static $loaded = false;
  private static $test_session = array();

  public static function setup_if_required()
  {
    if (self::$loaded) return;
    if (Config::$env == "test") return;

    session_set_cookie_params(null, '/', null, Cookies::$default_secure);
    session_start();
    self::$loaded = true;
  }

  public static function get($key)
  {
    self::setup_if_required();
    return Config::$env == "test" ? self::$test_session[$key] : $_SESSION[$key];
  }

  public static function set($key, $value)
  {
    self::setup_if_required();

    if (Config::$env == "test")
      self::$test_session[$key] = $value;
    else
      $_SESSION[$key] = $value;
  }

  public static function reset_for_test()
  {
    self::$test_session = array();
  }
}
?>
