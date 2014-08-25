<?php
class ExampleUserTest extends TestCase
{
  public function setup()
  {
    $this->model = new ExampleModel();
  }

  public function test_basic_method()
  {
    $this->assert(!$this->model->basic_method(true));
    $this->assert($this->model->basic_method(false));
  }

  public function test_delegate_method_should_call_api()
  {
    $this->model->api_library = Phake::mock('ApiLibrary');
    Phake::when($this->model->api_library)->make_api_call()->thenReturn(array("success" => true));

    $this->assert($this->model->delegate_method());

    Phake::verify($this->model->api_library)->make_api_call();
  }
}

class ExampleModel
{
  public $api_library;

  public function __construct()
  {
    $api_library = new ApiLibrary();
  }

  public function basic_method($input)
  {
    return !$input;
  }

  public function delegate_method()
  {
    $result = $this->api_library->make_api_call();
    return $result["success"];
  }
}

class ApiLibrary
{
  public function make_api_call()
  {
    // External API call
  }
}
?>
