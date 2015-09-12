<?php
class MigrationController extends Controller
{
  public function index()
  {
    $this->render(Migration::$INDEX_VIEW);
  }

  public function run()
  {
    Migration::run_all_pending();
    $this->render(Migration::$RUN_VIEW);
  }

  public function revert()
  {
    Migration::revert_from_params();
    $this->render(Migration::$RUN_VIEW);
  }
}
?>
