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
//self::match("", "TestController#index");

// Example routing
//self::match("/user/view/:id", "TestController#index");
?>
