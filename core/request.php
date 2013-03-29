<?php
class Request
{
  public static $method = "";
  public static $type = "html";
  public static $params = array();

  private static $method_locked = false;

  public static function setup()
  {
    foreach($_GET as $key => $val)
      self::$params[$key] = $val;
    foreach($_POST as $key => $val)
      self::$params[$key] = $val;
    if (!self::$method_locked)
      self::$method = $_SERVER["REQUEST_METHOD"];
  }

  public static function add_inline_params($params)
  {
    foreach($params as $name => $val)
      self::$params[$name] = $val;
  }

  /** ===== Testing helpers ===== **/
  public static function reset()
  {
    self::$type = "html";
    self::$params = array();
    self::$method = "";
  }
  public static function lock_method($method)
  {
    self::$method = $method;
    self::$method_locked = true;
  }

  public static function free_method()
  {
    self::$method_locked = false;
  }
}
?>