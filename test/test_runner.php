<?php
include 'test_case.php';

$phake_skip = array(
  "../test/Phake/Phake/Client/IClient.php",
  "../test/Phake/Phake/Stubber/IAnswerBinder.php",
  "../test/Phake/Phake/Stubber/IAnswerContainer.php",
  "../test/Phake/Phake/Stubber/IAnswer.php",
  "../test/Phake/Phake/Stubber/Answers/StaticAnswer.php",
  "../test/Phake/Phake/Matchers/IChainableArgumentMatcher.php",
  "../test/Phake/Phake/Matchers/AbstractChainableArgumentMatcher.php",
  "../test/Phake/Phake/Matchers/IMethodMatcher.php",
  "../test/Phake/Phake/Matchers/SingleArgumentMatcher.php",
  "../test/Phake/Phake/CallRecorder/IVerifierMode.php"
);
include_once 'Phake/Phake/Stubber/IAnswer.php';
foreach($phake_skip as $file)
{
  include_once "../test/" . $file;
}

$directory = new RecursiveDirectoryIterator('../test/Phake/');
$recIterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($recIterator, '/.*php$/i');

foreach($regex as $item) {
  if (in_array($item, $phake_skip)) continue;
  include $item->getPathname();
}

Config::$env = "test";
Database::open_connection();

class TestRunner
{

  public function run($test_type, $test_case)
  {
    $test_file_name = $test_case."_test";
    include "../test/$test_type/$test_file_name.php";
    $class_name = StringHelper::underscore_to_camel($test_file_name);
    $test = new $class_name;
    return $test->run();
  }
}
?>
