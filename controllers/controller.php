<?php
class Controller
{
  protected $layout = "";
  private $view = "";

  function __construct()
  {
    Database::open_connection();
  }

  /** ===== Response Methods ===== **/
  protected function render($view)
  {
    if (Config::$env == "test")
    {
      Response::$status = '200';
      Response::$type = "html";
    }

    Debug::flush_to_console();
    if ($this->layout == "")
      include_once $view;
    else
    {
      $this->view = $view;
      include_once $this->layout;
    }
  }

  protected function redirect($route)
  {
    Router::redirect_to($route);
  }

  protected function respond_with_json($json_object)
  {
    if (Config::$env == "test")
    {
      Response::$status = '200';
      Response::$type = "json";
      Response::$redirected_to = $route;
    }
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
    include_once $this->view;
  }

  /** ===== View Generation Helpers ===== **/
  public function content_for_header()
  {
    $namespace = Router::$app_namespace;
    echo("<script type='text/javascript'>var __APP_NAMESPACE = '$namespace';</script>");

    $tourniquet_js = Router::url_for('/assets/javascript/tourniquet.js');
    echo("<script type='text/javascript' src='$tourniquet_js'></script>");
  }


  public $form_object = null;

  public function form_for($model, $action, $method = "post")
  {
    $this->form_object = $model;
    $url = Router::url_for($action);
    echo("<form action='$url' method='$method'>");
  }

  public function end_form()
  {
    $this->form_object = null;
    echo("</form>");
  }

  public function text_input($attribute)
  {
    echo("<input type='text' ".$this->form_attributes_for($attribute)." value='".$this->form_object->get($attribute)."' />");
  }

  public function password_input($attribute)
  {
    echo("<input type='password' ".$this->form_attributes_for($attribute)." value='".$this->form_object->get($attribute)."' />");
  }

  public function hidden_input($attribute)
  {
    echo("<input type='input' ".$this->form_attributes_for($attribute)." value='".$this->form_object->get($attribute)."' />");
  }

  public function date_input($attribute)
  {
    echo("<input type='date' ".$this->form_attributes_for($attribute)." value='".$this->form_object->get($attribute)."' />");
  }

  public function text_area($attribute)
  {
    echo("<textarea ".$this->form_attributes_for($attribute).">".$this->form_object->get($attribute)."</textarea>");
  }

  public function check_box($attribute)
  {
    $checked = $this->form_object->get($attribute) ? " checked='checked'" : "";
    echo("<input type='hidden' ".$this->form_name_for($attribute)." value='0' />");
    echo("<input type='checkbox' ".$this->form_attributes_for($attribute)." value='1'$checked />");
  }

  public function radio_button($attribute, $value)
  {
    $checked = $this->form_object->get($attribute) == $value ? "checked='checked' " : " ";
    $class_name = $this->form_object->name();
    echo("<input type='radio' id='".$class_name."_".$attribute."_$value' ".$this->form_name_for($attribute)." value='$value' $checked/>");
  }

  public function form_attributes_for($attribute)
  {
    $class_name = $this->form_object->name();
    return "id='".$class_name."_$attribute' ".$this->form_name_for($attribute);
  }

  public function form_name_for($attribute)
  {
    $class_name = $this->form_object->name();
    return "name='".$class_name."[$attribute]'";
  }
}
?>
