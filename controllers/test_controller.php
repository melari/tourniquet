<?php
Router::load_resource("/controllers/controller.php");

class TestController extends Controller
{
  protected static $respond_to = array(
    "html" => array("run_all"),
    "json" => array("run_test_case")
  );

  public function run_all()
  {
    $this->tests = array();
    $this->total_count = 0;

    $types = array("unit", "functional");
    foreach($types as $type)
    {
      $tests_for_type = array();

      $dh = opendir("../test/$type");
      while(false !== ($filename = readdir($dh))) {
        if (!StringHelper::ends_with($filename, ".php")) continue;
        array_push($tests_for_type, substr($filename, 0, -9));
        $this->total_count++;
      }
      $this->tests[$type] = $tests_for_type;
    }

    $this->render("ci");
  }

  public function run_test_case()
  {
    Router::load_resource("/test/test_runner.php");
    $runner = new TestRunner();
    $results = $runner->run(Request::$params["type"], Request::$params["case"]);
    $this->respond_with_json($results);
  }
}
?>
