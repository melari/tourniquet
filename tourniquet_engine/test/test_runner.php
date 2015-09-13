<?php
include 'test_case.php';

$phake_skip = array(
  "Phake/Phake/ClassGenerator/InvocationHandler/IInvocationHandler.php",
  "Phake/Phake/ClassGenerator/ILoader.php",
  "Phake/Phake/Client/IClient.php",
  "Phake/Phake/Stubber/IAnswerBinder.php",
  "Phake/Phake/Stubber/IAnswerContainer.php",
  "Phake/Phake/Stubber/IAnswer.php",
  "Phake/Phake/Stubber/Answers/StaticAnswer.php",
  "Phake/Phake/Matchers/IChainableArgumentMatcher.php",
  "Phake/Phake/Matchers/AbstractChainableArgumentMatcher.php",
  "Phake/Phake/Matchers/IMethodMatcher.php",
  "Phake/Phake/Matchers/SingleArgumentMatcher.php",
  "Phake/Phake/CallRecorder/IVerifierMode.php"
);
Router::load_resource('tourniquet_engine/test/Phake/Phake/Stubber/IAnswer.php');
foreach($phake_skip as $file)
{
  Router::load_resource("tourniquet_engine/test/$file");
}

$directory = new RecursiveDirectoryIterator('../tourniquet_engine/test/Phake/');
$recIterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($recIterator, '/.*php$/i');

foreach($regex as $item) {
  if (in_array(substr($item, 26), $phake_skip)) continue;
  include $item->getPathname();
}

Config::$env = "test";
Database::open_connection();

class TestRunner
{

  public function run($test_type, $test_case)
  {
    $test_file_name = $test_case."_test";
    Router::load_resource("test/$test_type/$test_file_name.php");
    $class_name = StringHelper::underscore_to_camel($test_file_name);
    $test = new $class_name;
    return $test->run();
  }
}
?>
