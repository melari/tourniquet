<?php
/*
 * The response class holds information about the last
 * request made using Testcase::request().
*/
class Response
{
  public static $status;  // 200, 302, 404...
  public static $type;    // html, json...
  public static $content; // The actual content that would be sent to the user.
  public static $redirected_to; // Holds the url the request was redirected to during a 302
}

class TestCase
{
  public function setup() { }
  public function teardown() { }

  private $failures = array();
  public $pass_count = 0;
  public $fail_count = 0;
  private $current_test_name = "";
  private $current_case_name = "";

  public function run()
  {
    $this->current_case_name = get_class($this);
    $this->pass_count = 0;
    $this->fail_count = 0;

    $methods = get_class_methods($this);
    foreach($methods as $method)
    {
      if (!StringHelper::starts_with($method, "test"))
        continue;
      $this->current_test_name = $method;
      $this->failures = array();
      $this->setup();
      call_user_func(array($this, $method));
      $this->teardown();
      $this->global_teardown();
      if (count($this->failures) == 0)
      {
        $this->pass_count++;
        echo(".");
      }
      else
      {
        $this->fail_count++;
        $this->display_errors();
      }
    }

  }

  private function display_errors()
  {
    echo("<font color='red'>F<br />");
    foreach($this->failures as $failure)
    {
      Debug::error($failure);
      echo($failure."<br/>");
    }
    echo("</font><hr/>");
  }

  private function add_failure($message)
  {
    array_push($this->failures, "Failure in test '".$this->current_case_name."#".$this->current_test_name."': $message");
  }

  /** ===== Assertion Helper functions ===== **/
  protected function assert($statement)
  {
    if (!$statement)
      $this->add_failure(json_encode($statement)." is not true.");
  }

  protected function assert_equal($expectation, $actual)
  {
    if ($expectation !== $actual)
      $this->add_failure("expected ".json_encode($expectation)." but found ".json_encode($actual));
  }

  /** ===== Fixture helper functions ===== **/
  public function fixture($model, $fixture_name)
  {
    $json = $this->load_fixture_json($model);
    return new $model($json[$fixture_name]);
  }

  public function load_fixtures_to_database($model)
  {
    $existing = $model::all();
    foreach($existing as $to_delete)
    {
      $to_delete->destroy();
    }

    $json = $this->load_fixture_json($model);
    foreach($json as $_ => $params)
    {
      $fixture = new $model($params);
      $fixture->save();
    }
  }

  private function load_fixture_json($model)
  {
    $fixture_file = "fixtures/".StringHelper::camel_to_underscore($model).".json";
    return json_decode(file_get_contents($fixture_file), true);
  }

  /** ===== Functional GET/POST Request Helpers ===== **/
  protected function request($method, $url, $params = array())
  {
    Router::load_routes_config();
    Request::reset();
    Request::lock_method($method);
    Request::add_inline_params($params);
    ob_start(); //capture echo output to buffer
    try { Router::route_url(Router::$app_namespace.$url); }
    catch(Exception $e) { }
    Response::$content = ob_get_clean();
  }

  private function global_teardown()
  {
    Request::free_method();
  }
}
?>
