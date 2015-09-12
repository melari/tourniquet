<?php
class Config
{
  public static $env = "production";
  public static $server = "mainline";
  public static $mobile = false;
  public static $app_run_directory = "";

  /** NOTE: Config::setup should be called AFTER Request::setup **/
  /** Set up server and env variables here... **/
  public static function setup()
  {
    Router::load_resource('config/config.php');
  }
}
?>
