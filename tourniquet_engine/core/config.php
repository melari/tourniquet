<?php
class Config
{
  public static $env = "production";
  public static $server = "mainline";
  public static $mobile = false;
  public static $app_run_directory = "";
  public static $secrets = array();

  /** NOTE: Config::setup should be called AFTER Request::setup **/
  /** Set up server and env variables here... **/
  public static function setup()
  {
    $raw = file_get_contents(Router::path_for("config/secrets.json"));
    if ($raw !== false)
    {
      self::$secrets = json_decode($raw, true);
    }

    Router::load_resource('config/config.php');
  }
}
?>
