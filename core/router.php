<?php
class Router
{
  private static $ROUTES = array();
  private static $ERRORS = array();
  private static $cur_namespace = "";
  public static $app_namespace = "";

  public static function url_for($asset_name)
  {
    return self::$app_namespace.$asset_name;
  }

  private static function app_namespace($path)
  {
    self::$app_namespace =  $path;
  }

  private static function using_namespace($path)
  {
    self::$cur_namespace = $path;
  }

  private static function match($path, $action)
  {
    $path = self::$app_namespace.self::$cur_namespace.$path;

    $inline_params = array();
    $parts = explode('/', $path);
    $regex = "/^";
    foreach($parts as $part)
    {
      if (StringHelper::starts_with($part, ":"))
      {
        $regex .= "([^\/]+)\/";
        $inline_params[substr($part, 1)] = null;
      }
      else
        $regex .= preg_quote($part)."\/";
    }
    $regex .= "?\z/";

    self::$ROUTES[$regex] = array("inline_params" => $inline_params, "action" => $action);
  }

  private static function match_error($error, $path)
  {
    self::$ERRORS[$error] = $path;
  }

  public static function load_routes_config()
  {
    include_once '../config/routes.php';
  }

  public static function route_url($uri)
  {
    # Setup the Request and Config classes
    Request::setup();
    Config::setup();

    self::load_routes_config();

    $ext_start = strpos($uri, ".");
    if ($ext_start === false)
    {
      $query_start = strpos($uri, "?");
      if ($query_start === false)
        $route = self::find_route($uri);
      else
        $route = self::find_route(substr($uri, 0, $query_start));
    }
    else
    {
      $route = self::find_route(substr($uri, 0, $ext_start));

      # Parse out filetype.
      $type = substr($uri, $ext_start);
      $query_start = strpos($type, "?");
      if ($query_start === false)
        Request::$type = substr($type, 1);
      else
        Request::$type = substr($type, 1, $query_start-1);
    }

    Debug::log("[ROUTER] Started routing to: ".$route['action']." as ".Request::$type, '#2E8C44');
    $controller_action = explode('#', $route['action']);

    # Handle error routes
    if (isset($route['error']))
      self::redirect_to(self::$ERRORS[$route['error']]);

    # Load controller class by convention.
    include_once '../controllers/'.StringHelper::camel_to_underscore($controller_action[0]).".php";

    # Create controller
    $controller = new $controller_action[0];

    # Add the inline params to the request class
    Request::add_inline_params($route['inline_params']);

    # Call the acutal controller action.
    call_user_func(array($controller, $controller_action[1]));
  }

  public static function route_to_error($type)
  {
    self::redirect_to(self::$ERRORS[$type]);
  }

  private static function find_route($path)
  {
    $matches = array();
    foreach (self::$ROUTES as $regex => $route)
    {
      if (preg_match($regex, $path, $matches))
      {
        $match_id = 1;
        foreach($route['inline_params'] as $param => $_)
        {
          $route['inline_params'][$param] = $matches[$match_id];
          $match_id++;
        }
        return $route;
      }
    }
    return array('error' => '404');
  }

  public static function redirect_to($path)
  {
    if (Config::$env == "test")
    {
      Response::$status = '302';
      Response::$redirected_to = $route;
      throw new Exception("test_exit");
    }

    header("Location:$path");
    exit;
  }
}
?>