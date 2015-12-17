<?php
class Migration extends Model
{
  public static $INDEX_VIEW = '../tourniquet_engine/migrations/index';
  public static $RUN_VIEW = '../tourniquet_engine/migrations/run';

  protected static $attributes = array("sha", "ran_at");
  protected static $table = "schema_migrations";

  public $name = "Unnamed Migration";

  public function run()
  {
    $m = "migration " . $this->get("sha") . " (" . $this->name . ")";
    Debug::log("==== Running $m. ====");
    $this->set("ran_at", time());
    Database::$raise_on_error = true;
    $success = false;
    try {
      Database::query("BEGIN");
      $this->migrate();
      Database::query("COMMIT");
      $success = true;
    } catch(Exception $e) {
      Debug::error($e->getMessage());
      Database::query("ROLLBACK");
      $success = false;
    }
    Database::$raise_on_error = false;
    if ($success && $this->save()) {
      Debug::log("==== $m completed. ====");
      return true;
    }
    else {
      Debug::error("==== $m failed. Aborting all following migrations ====");
      return false;
    }
  }

  public function run_revert()
  {
    if ($this->name == "Unnamed Migration")
    {
      foreach(scandir("../migrations/") as $entry) {
        if (StringHelper::starts_with($entry, $this->get("sha"))) {
          include_once "../migrations/$entry";
          $parts = explode("_", $entry);
          $parts[count($parts)-1] = substr($parts[count($parts)-1], 0, -4);
          $sha = array_shift($parts);
          $class_name = StringHelper::underscore_to_camel(implode("_", $parts));
          $migration = new $class_name;
          $migration->set("sha", $sha);
          $migration->name = $class_name;
          return $migration->run_revert();
        }
      }
    }


    $m = "migration " . $this->get("sha") . " (" . $this->name . ")";
    Debug::log("==== Reverting $m. ====");
    Database::$raise_on_error = true;
    $success = false;
    try {
      Database::query("BEGIN");
      $this->revert();
      Database::query("COMMIT");
      $success = true;
    } catch(Exception $e) {
      Debug::error($e->getMessage());
      Database::query("ROLLBACK");
      $success = false;
    }
    Database::$raise_on_error = false;
    if ($success) {
      Migration::destroy_all(array("sha" => $this->get("sha")));
      Debug::log("==== $m reverted. ====");
      return true;
    }
    else {
      Debug::error("==== Revert of $m failed. Aborting all following migrations ====");
      return false;
    }
  }

  protected function migrate()
  {
    throw new Exception("Migration not implemented.");
  }

  protected function revert()
  {
    throw new Exception("Migration Revert not implemented.");
  }

  public function is_completed()
  {
    return !!Migration::find_one(array("sha" => $this->get("sha")));
  }

  public function is_pending()
  {
    return !$this->is_completed();
  }

  public static function run_all_pending()
  {
    self::create_schema_migrations_table();
    foreach(self::pending() as $migration)
    {
      if (!$migration->run()) { return; }
    }
  }

  public static function pending()
  {
    self::create_schema_migrations_table();
    return ArrayHelper::filter(self::all(), function($migration) {
      return $migration->is_pending();
    });
  }

  public static function completed()
  {
    self::create_schema_migrations_table();
    return array_reverse(ArrayHelper::filter(self::all(), function($migration) {
      return $migration->is_completed();
    }));
  }

  public static function all()
  {
    self::create_schema_migrations_table();
    $results = array();
    foreach(scandir("../migrations/") as $entry)
    {
      if (StringHelper::starts_with($entry, '.')) { continue; }
      Router::load_resource("migrations/$entry");
      $parts = explode("_", $entry);
      $parts[count($parts)-1] = substr($parts[count($parts)-1], 0, -4);
      $sha = array_shift($parts);
      $class_name = StringHelper::underscore_to_camel(implode("_", $parts));
      $migration = new $class_name;
      $migration->set("sha", $sha);
      $migration->name = $class_name;
      array_push($results, $migration);
    }

    usort($results, function($a, $b) {
      return $a->get("sha") - $b->get("sha");
    });
    return $results;
  }

  public static function revert_from_params()
  {
    self::create_schema_migrations_table();
    $migration = Migration::find_one(array("sha" => Request::$params["sha"]));
    $migration->run_revert();
  }

  public static function create_schema_migrations_table()
  {
    if (mysql_num_rows(Database::query("SHOW TABLES LIKE 'schema_migrations'")) == 0)
    {
      Debug::warn("schema_migrations table does not exist. Creating...");

      Database::query("
        CREATE TABLE schema_migrations (
          `id` INT(11) PRIMARY KEY AUTO_INCREMENT NOT NULL,
          `sha` INT(11) NOT NULL,
          `ran_at` INT(11) NOT NULL
        )
      ", true);
    }
  }
}
?>
