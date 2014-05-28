<?php
class Controller
{
  protected $layout = "";
  private $view = "";

  protected $connection_options = array();
  protected static $respond_to = null;

  public function before_filter($action)
  {
    Database::open_connection($this->connection_options);
    if (static::$respond_to != null)
    {
      $found = false;
      foreach(static::$respond_to[Request::$type] as $valid_action)
        if ($valid_action == $action)
          $found = true;
      if (!$found)
        $this->respond_with_error("404");
    }
  }

  public function after_filter($action) { }

  protected function run_for($action, $actions)
  {
    return in_array($action, $actions);
  }

  protected function run_for_all_except($action, $actions)
  {
    return !in_array($action, $actions);
  }

  /** ===== Response Methods ===== **/
  protected function render($view)
  {
    if (Config::$env == "test")
    {
      Response::$status = '200';
      Response::$type = "html";
    }

    Session::setup_if_required();
    $this->view = $view;
    if ($this->layout == "")
      $this->show_view();
    else
      include Router::path_for("views/layouts/$this->layout.html.php");
    Debug::flush_to_console();
  }

  protected function redirect($route)
  {
    if (StringHelper::starts_with($route, "http"))
      Router::redirect_to($route);
    else
      Router::redirect_to(Router::url_for($route));
  }

  protected function respond_with_json($json_object)
  {
    if (Config::$env == "test")
    {
      Response::$status = '200';
      Response::$type = "json";
      Response::$redirected_to = $route;
    }
    if (is_string($json_object))
      echo($json_object);
    else
      echo(json_encode($json_object));
  }

  protected function respond_with_error($type)
  {
    if (Config::$env == "test")
    {
      Response::$status = $type;
      throw new Exception("test_exit");
    }
    Router::route_to_error($type);
  }

  private function show_view()
  {
    include_once Router::path_for("views/$this->view.html.php");
  }

  /** ===== View Generation Helpers ===== **/
  public function content_for_header()
  {
    echo("<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>");
    echo("<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'></script>");

    $namespace = Router::$app_namespace;
    echo("<script type='text/javascript'>var __APP_NAMESPACE = '$namespace';</script>");

    $tourniquet_js = Router::url_for('/assets/javascript/tourniquet.js');
    echo("<script type='text/javascript' src='$tourniquet_js'></script>");
  }

  public function image($src, $hover_src = null, $options = array())
  {
    $result = "<img src='$src' ";
    if ($hover_src)
      $result .= "onmouseover=\"this.src='$hover_src';\" onmouseout=\"this.src='$src';\" ";
    $result .= $this->form_options($options);
    $result .= " />";
    return $result;
  }


  public $form_object = null;

  public function form_for_javascript($model, $action)
  {
    $this->form_object = $model;
    echo("<form onsubmit='return javascript_form($action)'>");
  }

  public function form_for($model, $action, $method = "post")
  {
    $this->form_object = $model;
    $url = Router::url_for($action);
    echo("<form action='$url' method='$method'>");
  }

  public function form_for_params($action, $method = "post")
  {
    $this->form_object = null;
    $url = Router::url_for($action);
    echo("<form action='$url' method='$method'>");
  }

  public function end_form()
  {
    $this->form_object = null;
    echo("</form>");
  }

  public function text_input($attribute, $options = array())
  {
    echo("<input type='text' ".$this->form_attributes_for($attribute)." value='".$this->form_value_for($attribute, $options)."' ".$this->form_options($options)."/>");
  }

  public function password_input($attribute, $options = array())
  {
    echo("<input type='password' ".$this->form_attributes_for($attribute)." value='".$this->form_value_for($attribute, $options)."' ".$this->form_options($options)."/>");
  }

  public function hidden_input($attribute, $options = array())
  {
    echo("<input type='input' ".$this->form_attributes_for($attribute)." value='".$this->form_value_for($attribute, $options)."' ".$this->form_options($options)."/>");
  }

  public function date_input($attribute, $options = array())
  {
    echo("<input type='date' ".$this->form_attributes_for($attribute)." value='".$this->form_value_for($attribute, $options)."' ".$this->form_options($options)."/>");
  }

  public function text_area($attribute, $options = array())
  {
    echo("<textarea ".$this->form_attributes_for($attribute)." ".$this->form_options($options).">".$this->form_value_for($attribute, $options)."</textarea>");
  }

  public function check_box($attribute, $options = array())
  {
    $checked = $this->form_value_for($attribute, $options) ? " checked='checked'" : "";
    echo("<input type='hidden' ".$this->form_name_for($attribute)." value='0' ".$this->form_options($options)."/>");
    echo("<input type='checkbox' ".$this->form_attributes_for($attribute)." value='1'$checked ".$this->form_options($options)."/>");
  }

  public function radio_button($attribute, $value, $options = array())
  {
    $checked = $this->form_value_for($attribute, $options) == $value ? "checked='checked' " : " ";
    $class_name = $this->form_object->name();
    echo("<input type='radio' id='".$class_name."_".$attribute."_$value' ".$this->form_name_for($attribute)." value='$value' $checked".$this->form_options($options)."/>");
  }

  public function select_box($attribute, $values, $options = array())
  {
    echo("<select ".$this->form_attributes_for($attribute)." ".$this->form_options($options).">");
    $selected_value = $this->form_value_for($attribute, $options);
    foreach($values as $value => $text)
    {
      $selected = $value == $selected_value ? "selected='selected'" : "";
      echo("<option value='$value' $selected>$text</option>");
    }
    echo("</select>");
  }

  public function form_attributes_for($attribute)
  {
    if ($this->form_object == null)
      return "id='$attribute' name='$attribute'";

    $class_name = $this->form_object->name();
    return "id='".$class_name."_$attribute' ".$this->form_name_for($attribute);
  }

  public function form_name_for($attribute)
  {
    if ($this->form_object == null)
      return "name='$attribute'";

    $class_name = $this->form_object->name();
    return "name='".$class_name."[$attribute]'";
  }

  public function form_value_for($attribute, $options)
  {
    $value = $this->form_object == null ? Request::$params[$attribute] : $this->form_object->get($attribute);
    if (!$value && isset($options["default"]))
      $value = $options["default"];
    return htmlentities($value, ENT_QUOTES);
  }

  public function form_options($options)
  {
    $result = "";
    foreach($options as $key => $value)
    {
      $result .= "$key='$value' ";
    }
    return $result;
  }
}
?>
