<?php
class TourniquetCI
{
  public static $RUN_VIEW = '../tourniquet_engine/test/ci';

  public static $tests = array();
  public static $total_count = 0;

  public static function select_all_tests()
  {
    self::$tests = array();
    self::$total_count = 0;

    $types = array("unit", "functional");
    foreach($types as $type)
    {
      $tests_for_type = array();

      $dh = opendir("../test/$type");
      while(false !== ($filename = readdir($dh))) {
        if (!StringHelper::ends_with($filename, ".php")) continue;
        array_push($tests_for_type, substr($filename, 0, -9));
        self::$total_count++;
      }
      self::$tests[$type] = $tests_for_type;
    }
  }

  public static function run_from_params()
  {
    Router::load_resource("tourniquet_engine/test/test_runner.php");
    $runner = new TestRunner();
    return $runner->run(Request::$params["type"], Request::$params["case"]);
  }
}
?>
