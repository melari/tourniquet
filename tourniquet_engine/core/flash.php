<?php
class Flash
{
  private static $message = "";
  private static $type = "";
  private static $setup = false;

  public static function setup_if_needed()
  {
    if (self::$setup) return;
    self::$setup = true;

    self::$message = Session::get('__flash_message');
    self::$type = Session::get('__flash_type');
    self::clear();
  }

  public static function clear()
  {
    self::set("", "");
  }

  public static function set($type, $message)
  {
    self::setup_if_needed();
    Session::set('__flash_message', $message);
    Session::set('__flash_type', $type);
  }

  public static function set_now($type, $message)
  {
    self::setup_if_needed();
    self::$message = $message;
    self::$type = $type;
  }

  public static function type()
  {
    self::setup_if_needed();
    if (self::$type == "") return null;
    return self::$type;
  }

  public static function message()
  {
    self::setup_if_needed();
    if (self::$message == "") return null;
    return self::$message;
  }
}
?>
