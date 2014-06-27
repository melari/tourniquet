<?php
class Config
{
  public static $env = "production";
  public static $server = "master";
  public static $app_run_directory = ""; #point to index.php file when running from a subdirectory.

  /** NOTE: Config::setup must be called AFTER Request::setup **/
  /** Set up server and env variables here... **/
  public static function setup()
  {
    if (isset(Request::$params["__debug__"]))
    {
      self::$env = "debug";
      Debug::log("[ROUTER] Running in debug environment.", "purple");
    }

    if (StringHelper::contains($_SERVER['SERVER_NAME'], "sub.domain.com"))
      self::$server = "sub-domain";
    else if (StringHelper::contains($_SERVER['SERVER_NAME'], "domain.com"))
      self::$server = "direct";
  }
}
?>
