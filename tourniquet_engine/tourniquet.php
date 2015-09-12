<?php
/* =================================================================================
 *                            Tourniquet Engine
 *                    See LICENSE file for license details
 *
 *   Tournqiuet::start()        -  load the engine and route to the current URL.
 *   Tourniquet::load_engine()  -  load the engine without routing anywhere.
 *   ============================================================================== */

class Tourniquet
{
  public static $VERSION = '1.0.0';

  public static function start()
  {
    self::load_engine();
    Router::route_url($_SERVER['REQUEST_URI']);
  }

  public static function load_engine()
  {
    self::load_dir('tourniquet_engine/core');
    self::load_dir('tourniquet_engine/helpers');
    self::load_dir('helpers');

    Router::load_resource('tourniquet_engine/test/tourniquet_ci.php');
    Router::load_resource('tourniquet_engine/migrations/migration.php');

    Request::setup();
    Config::setup();
  }

  private static function load_dir($dir)
  {
    foreach(glob("../$dir/*.php") as $file) {
      include_once $file;
    }
  }
}
?>
