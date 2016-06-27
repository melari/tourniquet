<?php
class Request
{
  public static $method = "";
  public static $type = "html";
  public static $params = array();
  public static $files = array();
  public static $uri = "";
  public static $route_namespace = "";
  public static $remote_ip = "";

  private static $method_locked = false;
  private static $test_uri = "";

  public static function setup()
  {
    foreach($_GET as $key => $val)
      self::$params[$key] = $val;
    foreach($_POST as $key => $val)
      self::$params[$key] = $val;
    self::$files = $_FILES;
    if (!self::$method_locked)
      self::$method = $_SERVER["REQUEST_METHOD"];
    self::$remote_ip = $_SERVER["REMOTE_ADDR"];
  }

  public static function add_inline_params($params)
  {
    foreach($params as $name => $val)
      self::$params[$name] = $val;
  }

  public static function full_request_uri()
  {
    return Config::$env == "test" ? self::$test_uri : $_SERVER['REQUEST_URI'];
  }

  /** ===== Testing helpers ===== **/
  public static function reset()
  {
    self::$type = "html";
    self::$params = array();
    self::$files = array();
    self::$method = "";
  }

  public static function set_test_uri($uri)
  {
    self::$test_uri = $uri;
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
