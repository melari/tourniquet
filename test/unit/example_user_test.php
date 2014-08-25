<?php
class ExampleTest extends TestCase
{
  public function setup()
  {
    $this->model = new ExampleModel();
  }

  public function test_basic_method()
  {
    $this->assert($this->model->basic_method(true));
    $this->assert(!$this->model->basic_method(false));
  }
}

class ExampleModel
{
  public function basic_method($input)
  {
    return $input;
  }
}
?>
