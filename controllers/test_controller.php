<?php
class TestController extends Controller
{
  protected static $respond_to = array(
    "html" => array("run_all"),
    "json" => array("run_test_case")
  );

  public function run_all()
  {
    TourniquetCI::select_all_tests();
    $this->render(TourniquetCI::$RUN_VIEW);
  }

  public function run_test_case()
  {
    $this->respond_with_json(TourniquetCI::run_from_params());
  }
}
?>
