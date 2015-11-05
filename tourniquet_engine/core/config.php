<?php
class Config
{
  public static $env = "production";
  public static $server = "mainline";
  public static $mobile = false;
  public static $app_run_directory = "";
  public static $secrets_path = "config/secrets.json";
  public static $secrets = array();

  /** NOTE: Config::setup should be called AFTER Request::setup **/
  /** Set up server and env variables here... **/
  public static function setup()
  {
    self::reload_secrets();
    Router::load_resource('config/config.php');
  }

  public static function reload_secrets()
  {
    $raw = @file_get_contents(Router::path_for(self::$secrets_path));
    if ($raw !== false)
    {
      self::$secrets = json_decode($raw, true);
    }
  }
}
?>
