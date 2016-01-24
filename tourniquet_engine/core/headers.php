<?php
class Headers
{
  private static $incoming = null;

  public static function get($name)
  {
    if (self::$incoming == null) { self::$incoming = getallheaders(); }
    return self::$incoming[$name];
  }

  public static function set($name, $value)
  {
    header("$name: $value");
  }
}
?>
