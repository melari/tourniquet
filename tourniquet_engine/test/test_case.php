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
  public static $rendered; // The name of the view that was rendered
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
    $this->fail_details = array();
    $this->success_list = array();

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
      Session::reset_for_test();
      $this->setup();
      try {
        call_user_func(array($this, $method));
      } catch(Exception $e) {
        $this->add_failure($e->getMessage());
      }

      $this->teardown();
      $this->global_teardown();
      if (count($this->failures) == 0)
      {
        $this->pass_count++;
        array_push($this->success_list, $method);
      }
      else
      {
        $this->fail_count++;
        array_push($this->fail_details, $this->failures);
      }
    }

    return array(
      "success" => $this->pass_count,
      "failure" => $this->fail_count,
      "details" => $this->fail_details,
      "success_list" => $this->success_list
    );
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

  protected function assert_null($statement)
  {
    $this->assert_equal(null, $statement);
  }

  /** ===== Fixture helper functions ===== **/
  public function fixture($model, $fixture_name)
  {
    $json = $this->load_fixture_json($model);
    return new $model($json[$fixture_name], true);
  }

  public function load_fixtures_to_database($model)
  {
    $model::destroy_all(true);

    $success = true;
    if ($json = $this->load_fixture_json($model))
    {
      foreach($json as $_ => $params)
      {
        $fixture = new $model($params, true);
        if (!$fixture->save())
          $success = false;
      }
    }
    else
    {
      $success = false;
    }

    if (!$success)
    {
      Debug::error("Failed to load fixtures: $model. There is likely a syntax error in the json.");
      Debug::flush_to_console();
    }
  }

  public function load_nnmap($table_name)
  {
    Database::query("DELETE FROM `$table_name`;");

    $success = true;
    if ($json = json_decode(file_get_contents("../test/fixtures/nnmaps/$table_name.json"), true))
    {
      $columns = $json["columns"];
      $first_column = $columns[0];
      $second_column = $columns[1];
      foreach ($json["rows"] as $row)
      {
        $first_value = $row[0];
        $second_value = $row[1];
        if (!Database::query("INSERT INTO `$table_name` (`$first_column`, `$second_column`) VALUES ($first_value, $second_value);"))
          $success = false;
      }
    }
    else
    {
      $success = false;
    }

    if (!$success)
    {
      Debug::error("Failed to load nnmap: $table_name. There is likely a syntax error in the json.");
      Debug::flush_to_console();
    }
  }

  private function load_fixture_json($model)
  {
    $fixture_file = "../test/fixtures/".StringHelper::camel_to_underscore($model).".json";
    return json_decode(file_get_contents($fixture_file), true);
  }

  /** ===== Functional GET/POST Request Helpers ===== **/
  protected function request($method, $url, $params = array())
  {
    Router::load_routes_config();
    Request::reset();
    Flash::reset_for_test();
    Request::lock_method($method);
    Request::add_inline_params($params);
    Request::set_test_uri($url);
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
