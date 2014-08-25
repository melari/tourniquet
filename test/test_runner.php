<?php
include '../tourniquet_loader.php';
include 'test_case.php';

$phake_skip = array(
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
include_once 'Phake/Phake/Stubber/IAnswer.php';
foreach($phake_skip as $file)
{
  include_once $file;
}

$directory = new RecursiveDirectoryIterator('Phake/');
$recIterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($recIterator, '/.*php$/i');

foreach($regex as $item) {
  if (in_array($item, $phake_skip)) continue;
  include $item->getPathname();
}

Config::$env = "test";
Debug::log("[ROUTER] Running in test environment.", "purple");
Database::open_connection();

Request::setup();
Session::setup_if_required(); //Force session loading before the tests start echoing.


$test_types = array("unit", "functional");
foreach($test_types as $test_type)
{
  echo("== Running $test_type tests ==<br />");
  $pass_count = 0;
  $fail_count = 0;

  $unit_tests = opendir($test_type);
  while (false !== ($test_case = readdir($unit_tests)))
  {
    if (!StringHelper::ends_with($test_case, ".php"))
      continue;
    include "$test_type/$test_case";
    $class_name = StringHelper::underscore_to_camel(substr($test_case, 0, -4));
    $test = new $class_name;
    $test->run();
    $pass_count += $test->pass_count;
    $fail_count += $test->fail_count;
  }
  echo("<hr/>$pass_count Passed | $fail_count Failed");
  echo("<br/><br/>");
}

Debug::flush_to_console();
?>
