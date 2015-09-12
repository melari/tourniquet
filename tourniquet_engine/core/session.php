<?php
class Session
{
  private static $loaded = false;
  public static function setup_if_required()
  {
    if (self::$loaded) return;
    ini_set('session.gc_maxlifetime', 7*24*60*60);
    session_start();
    self::$loaded = true;
  }

  public static function get($key)
  {
    self::setup_if_required();
    return $_SESSION[$key];
  }

  public static function set($key, $value)
  {
    self::setup_if_required();
    $_SESSION[$key] = $value;
  }
}
?>
