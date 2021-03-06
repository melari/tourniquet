<?php
class Router
{
  private static $ROUTES = array();
  private static $ERRORS = array();
  private static $cur_namespace = "";
  public static $app_namespace = "";
  public static $path = "";

  public static function path_for($file)
  {
    return realpath($_SERVER["DOCUMENT_ROOT"]) . Config::$app_run_directory . "/../$file";
  }

  public static function load_resource($file)
  {
    include_once self::path_for($file);
  }

  public static function url_for($asset_name)
  {
    return self::$app_namespace.$asset_name;
  }

  public static function versioned_url_for($asset_name)
  {
    $url = self::url_for($asset_name);
    if (StringHelper::contains($url, '?'))
      return $url . "&v=" . Config::$version;
    else
      return $url . "?v=" . Config::$version;
  }

  public static function url_with_namespace_for($asset_name)
  {
    return self::$app_namespace.Request::$route_namespace.$asset_name;
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

    self::$ROUTES[$regex] = array("inline_params" => $inline_params, "action" => $action, "controller_path" => self::$cur_namespace);
  }

  private static function match_error($error, $action)
  {
    self::$ERRORS[$error] = $action;
  }

  public static function load_routes_config()
  {
    self::load_resource('config/routes.php');
  }

  public static function route_url($uri)
  {
    self::load_routes_config();

    $ext_start = strpos($uri, ".");
    if ($ext_start === false)
    {
      $query_start = strpos($uri, "?");
      $path = $query_start === false ? $uri : substr($uri, 0, $query_start);
      Request::$uri = str_replace(self::$app_namespace, "", $path);
      $route = self::find_route($path);
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

    # Handle error routes
    if (isset($route['error']))
      $route['action'] = self::route_to_error($route['error']);
    
    self::call_controller_for_route($route);
  }

  public static function route_to_error($type)
  {
    if (Config::$env == "test")
      Response::$status = $type;

    header("HTTP/1.0 $type");
    if (isset(self::$ERRORS[$type]))
      self::call_controller_for_route(array("action" => self::$ERRORS[$type], "inline_params" => array()));
    exit;
  }

  private static function call_controller_for_route($route)
  {
    $controller_action = explode('#', $route['action']);

    Request::$route_namespace = $route['controller_path'];

    # Load controller class by convention.
    self::load_resource('controllers'.$route['controller_path'].'/'.StringHelper::camel_to_underscore($controller_action[0]).".php");

    # Create controller
    $controller = new $controller_action[0];

    # Add the inline params to the request class
    Request::add_inline_params($route['inline_params']);

    # Call the actual controller action.
    $controller->before_filter($controller_action[1]);
    call_user_func(array($controller, $controller_action[1]));
    $controller->after_filter($controller_action[1]);
  }

  private static function find_route($path)
  {
    self::$path = $path;
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
      Response::$redirected_to = $path;
      throw new Exception("test_exit");
    }

    header("Location:$path");
    exit;
  }
}
?>
