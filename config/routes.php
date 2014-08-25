<?php
/*
Select an app_namespace depending on the server...

switch (Config::$server)
{
case "master":
case "sub-domain":
  self::app_namespace("/htdocs");
  break;

case "direct":
  self::app_namespace("/sub/htdocs");
  break;
}
 */

// Match errors.
//self::match_error("404", "/error404");

// Match the root
//self::match("", "ExampleController#index");

// Example routing
//self::match("/user/view/:id", "ExampleController#index");

// Routes for Tourniquet CI
// self::match("/ci", "TestController#run_all");
// self::match("/ci/:type/:case", "TestController#run_test_case");
?>
