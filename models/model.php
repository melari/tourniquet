<?php
class Model
{
  public $validation_errors = array();       # Holds the last validation errors that occured.

  protected static $attributes = array();           # A list of custom attributes that are loaded in the constructor (to be overriden)
  protected static $readonly_attributes = array("created_at", "updated_at");  # A list of attributes that are loaded from the database, but never saved.
  protected static $protected_attributes = array(); # A list of attributes that cannot be set using mass assignment. (to be overriden)
  protected static $table = "";              # Name of the corresponding database table (to be overridden)

  private $attr = array("id" => null);       # Model Attribute
  private $dirty_attr= array();
  private $readonly_attr = array();
  private $in_database = false;              # Keeps track if this model is saved to the database yet or not.
  private $last_saved_id;                    # The ID of this model as it is currently saved in the database (since $attr[id] might change)
  protected static $relations = null;              # Stores this model's relations.

  protected static $scopes = null;           # Stores this model's scopes

  public static function array_to_json($model_array, $whitelist = null)
  {
    $result = array();
    foreach($model_array as $model)
      array_push($result, $model->as_json());

    if ($whitelist)
      for($i=0; $i < count($result); $i++)
        foreach($result[$i] as $attribute => $value)
          if (!in_array($attribute, $whitelist))
            unset($result[$i][$attribute]);

    return $result;
  }

  public static function filter($model_array, $filter)
  {
    $result = array();
    foreach($model_array as $model)
    {
      if ($model->matches($filter))
        array_push($result, $model);
    }
    return $result;
  }

  /**
   * Basic constructor. If you pass in a numeric parameter, the database is searched
   * for an entry with that ID.
  **/
  function __construct($args = null)
  {
    foreach(static::$attributes as $attribute)
    {
      $this->attr[$attribute] = null;
    }
    foreach(static::$readonly_attributes as $attribute)
    {
      $this->readonly_attr[$attribute] = null;
    }

    if ($args != null)
    {
      if (is_numeric($args))
        $this->load_by_id($args);
      if (is_array($args))
        $this->update_from_params($args);
    }

    if (static::$relations == null)
      $this->mappings();
    if (static::$scopes == null)
      $this->scopes();
  }

  public function as_json()
  {
    return $this->attr;
  }

  public function name()
  {
    return StringHelper::camel_to_underscore(get_class($this));
  }

  /** Shortcut for getting the id of this model. **/
  public function id()
  {
    return $this->attr["id"];
  }

  /**
   * Simple accessor shortcut for getting a models attributes.
  **/
  public function get($name)
  {
    return isset($this->attr[$name]) ? $this->attr[$name] : $this->readonly_attr[$name];
  }

  /**
   * Simple setter shortcut for setting a model's attributes.
  **/
  public function set($name, $value)
  {
    if ($this->attr[$name] == $value) return;
    $this->attr[$name] = $value;
    array_push($this->dirty_attr, $name);
  }

  public function matches($filter)
  {
    foreach($filter as $key => $value)
    {
      if ($this->get($key) != $value)
        return false;
    }
    return true;
  }

  public function save()
  {
    if (!$this->run_validations())
    {
      if (Config::$env == "test" || Config::$env == "debug")
      {
        Debug::log("[Validation] ".count($this->validation_errors)." failure(s):", "red");
        Debug::log($this->validation_errors);
      }
      return false;
    }

    if (!$this->in_database)
      $this->dirty_attr = array_keys($this->attr);

    if (count($this->dirty_attr) == 0)
      return true;

    $command = $this->in_database ? "UPDATE" : "INSERT INTO";
    $query = $command." `".$this->table_name()."` SET";
    $first = true;
    foreach($this->dirty_attr as $attribute)
    {
      $value = $this->attr[$attribute];
      if ($command == "UPDATE" && $attribute == "id")
        continue;

      if (!$first)
        $query .= ",";
      $query .= " `".$attribute."`='".Database::sanitize($value)."'";
      $first = false;
    }

    if ($command == "UPDATE")
      $query .= " WHERE `id`='".Database::sanitize($this->id())."'";

    if (Database::query($query) !== false)
    {
      $this->load_by_id(mysql_insert_id());
      $this->dirty_attr = array();
      return true;
    }
    return false;
  }

  public static function destroy_all($params = array(), $use_callbacks = false)
  {
    if ($params === true)
      $params = array();
    else if (count($params) == 0)
      return Debug::warning("Calling Model::destroy_all with an empty params hash will delete all records from the database. If this is the intended action, call with Model::destroy_all(true)");

    $use_callbacks ? self::destroy_all_with_callbacks($params) : self::destroy_all_without_callbacks($params);
  }

  private static function destroy_all_with_callbacks($params)
  {
    foreach(self::find($params) as $model)
      $model->destroy_callbacks();
    destroy_all_without_callbacks($params);
  }

  private static function destroy_all_without_callbacks($params)
  {
    $query = "DELETE FROM `".self::table_name()."`";
    $where_query .= self::where_query($params, $conditions);
    if ($where_query != "")
      $query .= " WHERE$where_query";
    Database::query($query);
  }

  /** Removes this model from the database. **/
  public function destroy()
  {
    if ($this->in_database)
      Database::query("DELETE FROM `".$this->table_name()."` WHERE id='".$this->last_saved_id."'");
    $this->destroy_callbacks();
  }

  private function destroy_callbacks()
  {
    $this->in_database = false;
    $this->last_saved_id = null;
    $this->on_destroy();
  }

  protected function on_destroy() { } // Callback to be be overriden

  /** Delegation function just for aestetics. **/
  public static function all($conditions = array())
  {
    return self::find(array(), $conditions);
  }

  /** === SCOPE FUNCTIONS === **/
  public static function get_scope($scope, $conditions = array())
  {
    return self::find(static::$scopes[$scope], $conditions);
  }

  public function scopes() { }

  public function scope($name, $params)
  {
    static::$scopes[$name] = $params;
  }

  /**
   * Searches the database using the given parameters, returning an array of model
   *  objects corresponding to the results.
  **/
  public static function find($params, $conditions = array())
  {
    $selection = isset($conditions["count"]) ? "COUNT(*)" : "*";

    $query = sprintf("SELECT $selection FROM `%s`", self::table_name());
    if (count($params) > 0)
    {
      $where_query .= self::where_query($params, $conditions);
      if ($where_query != "")
        $query .= " WHERE$where_query";
    }

    $query .= self::add_conditions($conditions);

    return self::process_query($query, $selection == "COUNT(*)");
  }

  /**
   * Search the database using the given parameters for a single model.
   * Returns null if none was found.
  **/
  public static function find_one($params, $conditions = array())
  {
    $conditions["limit"] = 1;
    $result = self::find($params, $conditions);
    if (count($result) == 0) return null;
    return $result[0];
  }

  public static function process_query($query, $count = false)
  {
    $result_array = array();
    $result = Database::query($query);
    while($row = mysql_fetch_array($result))
    {
      if ($count)
        return intval($row[0]);

      $new_model = new static();
      $new_model->create_from_table_result($row);
      array_push($result_array, $new_model);
    }

    return $result_array;
  }

  public static function where_query($params, $conditions)
  {
    $first = true;
    $where_query = "";
    foreach($params as $name => $value)
    {
      if ($value == null && $conditions["exclude_null"]) continue;

      if (!$first)
        $where_query .= " AND ";
      else
        $where_query .= " ";

      if (StringHelper::starts_with($name, "LIKE"))
        $where_query .= sprintf("`%s` LIKE '%s'", Database::sanitize(substr($name, 4)), Database::sanitize($value));
      else if (StringHelper::starts_with($name, "%LIKE%"))
        $where_query .= sprintf("`%s` LIKE '%%%s%%'", Database::sanitize(substr($name, 6)), Database::sanitize($value));
      else
        $where_query .= sprintf("`%s`='%s'", Database::sanitize($name), Database::sanitize($value));

      $first = false;
    }
    return $where_query;
  }

  public static function add_conditions($conditions)
  {
    $result = "";
    if (isset($conditions["order_by"]))
      $result .= " ORDER BY " . Database::sanitize($conditions["order_by"]);
    if (isset($conditions["limit"]))
      $result .= " LIMIT " . Database::sanitize($conditions["limit"]);
    if (isset($conditions["offset"]))
      $result .= " OFFSET " . Database::sanitize($conditions["offset"]);
    return $result;
  }

  /**
   * Searches the database using the given raw SQL as the where clause.
   * WARNING: the user is responsible for protecting against SQL injection in this
   * case!! Be sure to make use of Database::sanitize()
  **/
  public static function query($query, $conditions = array())
  {
    $selection = isset($conditions["count"]) ? "COUNT(*)" : "*";
    $query .= self::add_conditions($conditions);
    $result_array = array();
    $result = Database::query(sprintf("SELECT $selection FROM `%s` WHERE %s", self::table_name(), $query));
    while($row = mysql_fetch_array($result))
    {
      if ($selection == "COUNT(*)")
        return intval($row[0]);

      $new_model = new static();
      $new_model->create_from_table_result($row);
      array_push($result_array, $new_model);
    }
    return $result_array;
  }

  /**
   * Accessor for the corresponding table name of this model.
  **/
  public static function table_name()
  {
    return Database::sanitize(static::$table);
  }

  /**
   * Finds the record with the given ID.
   * This is an optimized alternative to using find(id => ...)
  **/
  public function load_by_id($id)
  {
    $query = sprintf("SELECT * FROM `%s` WHERE `id`='%s'", self::table_name(), Database::sanitize($id));
    $result = Database::query($query);
    if ($row = mysql_fetch_array($result))
    {
      $this->create_from_table_result($row);
      return $this;
    }
  }

  /** Fills in the models attributes using a given table row. **/
  private function create_from_table_result($row)
  {
    foreach($this->attr as $attribute => $value)
    {
      $this->attr[$attribute] = $row[$attribute];
    }
    foreach($this->readonly_attr as $attribute => $value)
    {
      $this->readonly_attr[$attribute] = $row[$attribute];
    }
    $this->in_database = true;
    $this->last_saved_id = $this->attr["id"];
  }

  /** Fills in the model's attributes using a given param array. **/
  public function update_from_params($params)
  {
    foreach($this->attr as $attribute => $value)
    {
      if (in_array($attribute, static::$protected_attributes))
        continue;
      if (!isset($params[$attribute]))
        continue;
      $this->set($attribute, $params[$attribute]);
    }
  }

  /** ======== VALIDATION HELPER FUNCTIONS ======== **/
  public function is_valid()
  {
    return (count($this->validation_errors) == 0);
  }
  public function run_validations()
  {
    $this->validation_errors = array();
    $this->validate_uniqueness_of("id");
    $this->validations();
    return count($this->validation_errors) == 0;
  }

  public function validations() { }

  protected function validate_uniqueness_of($attribute, $error = "")
  {
    if (is_array($attribute))
    {
      $query = "SELECT COUNT(*) FROM `".$this->table_name()."` WHERE ";
      $first = true;
      foreach($attribute as $a)
      {
        if (!$first)
          $query .= " AND ";
        $query .= "`$a`='".Database::sanitize($this->get($a))."'";
        $first = false;
      } 
    }
    else
    {
      $query = "SELECT COUNT(*) FROM `".$this->table_name()."` WHERE `".$attribute."`='".Database::sanitize($this->get($attribute))."'";
    }
    if ($this->in_database)
      $query .= "AND id<>'".$this->last_saved_id."'";

    if (Database::count_query($query) > 0)
      $this->add_validation_error($error, print_r($attribute, true)." must be unique.");
  }

  protected function validate_presence_of($attribute, $error = "")
  {
    $value = $this->get($attribute);
    if ($value == null or $value == "")
      $this->add_validation_error($error, $attribute." must have a value.");
  }

  protected function validate_format_of($attribute, $format, $error = "")
  {
    $format = "/^".$format."\z/";
    if (preg_match($format, $this->get($attribute)) == false)
      $this->add_validation_error($error, $attribute." must match the form ".$format.".");
  }

  private function add_validation_error($custom_error, $fallback)
  {
    array_push($this->validation_errors, $custom_error == "" ? $fallback : $custom_error);
  }

  /** ========= MAPPING HELPER FUNCTION ========== **/
  public function mappings() { }

  protected function map($label, $type, $other_name, $relation_table = "")
  {
    static::$relations[$label] = array('type' => $type, 'other' => $other_name, 'table' => $relation_table);
  }

  public function get_map($label, $conditions = array())
  {
    if (!isset(static::$relations[$label]))
    {
      Debug::error("Relation $label does not exist.");
      Debug::log(static::$relations);
    }

    $relation = static::$relations[$label];
    $class_name_id = $this->name()."_id";
    $other_class_name = $relation['other'];
    $other_class_name_id = StringHelper::camel_to_underscore($other_class_name)."_id";
    $relation_table = $relation['table'];
    if ($relation_table == "")
    {
      $class_name_id_or_custom = $class_name_id;
      $other_class_name_id_or_custom = $other_class_name_id;
    }
    else
    {
      $class_name_id_or_custom = $relation_table;
      $other_class_name_id_or_custom = $relation_table;
    }

    switch($relation['type'])
    {
    case '1!-1':
      return new $other_class_name($this->get($other_class_name_id_or_custom));
      break;
    case '1-1!':
      if ($this->id() == "") Debug::error("Cannot follow 1-1! mapping without an ID");
      return new $other_class_name(array($class_name_id_or_custom => $this->id()));
      break;
    case '1-N':
      return new $other_class_name($this->get($other_class_name_id_or_custom));
      break;
    case 'N-1':
      if ($this->id() == "") Debug::error("Cannot follow N-1 mapping without an ID");
      return $other_class_name::find(array($class_name_id_or_custom => $this->id()), $conditions);
      break;
    case 'N-N':
      $my_id = $this->id();
      if ($my_id == "") Debug::error("Cannot follow N-N mapping without an ID");
      return $other_class_name::query("`id` IN (SELECT `$other_class_name_id` FROM `$relation_table` WHERE `$class_name_id`='$my_id')", $conditions);
      break;
    }
  }

  public function add_map($label, $other_model)
  {
    if (!isset(static::$relations[$label]))
    {
      Debug::error("Relation $label does not exist.");
      Debug::log(static::$relations);
    }

    $relation = static::$relations[$label];
    if ($relation['type'] != "N-N")
      Debug::error("Model::add_map can only be used with N-N relations.");

    $class_name_id = $this->name()."_id";
    $other_class_name = $relation['other'];
    $other_class_name_id = StringHelper::camel_to_underscore($other_class_name)."_id";
    $relation_table = $relation['table'];

    $my_id = $this->id();
    $other_id = $other_model->id();
    $existing = mysql_fetch_array(Database::query("SELECT COUNT(*) FROM $relation_table WHERE `$class_name_id`='$my_id' AND `$other_class_name_id`='$other_id' LIMIT 1"));
    if (intval($existing[0]) > 0)
      return false;
    Database::query("INSERT INTO $relation_table (`$class_name_id`, `$other_class_name_id`) VALUES('$my_id', '$other_id');");
    return true;
  }

  public function remove_map($label, $other_model)
  {
    $relation = $this->get_relation($label);
    if ($relation['type'] != "N-N")
      Debug::error("Model::remove_map can only be used with N-N relations.");

    $class_name_id = $this->name()."_id";
    $other_class_name = $relation['other'];
    $other_class_name_id = StringHelper::camel_to_underscore($other_class_name)."_id";
    $relation_table = $relation['table'];

    $my_id = $this->id();
    $other_id = $other_model->id();
    Database::query("DELETE FROM $relation_table WHERE `$class_name_id`='$my_id' AND `$other_class_name_id`='$other_id';");
  }

  public function remove_all_map($label)
  {
    $relation = $this->get_relation($label);
    if ($relation['type'] != "N-N")
      Debug::error("Model::remove_all_map can only be used with N-N relations.");

    $id = $this->id();
    $relation_table = $relation['table'];
    $class_name_id = $this->name()."_id";
    Database::query("DELETE FROM $relation_table WHERE `$class_name_id`='$id';");
  }

  public function map_contains($label, $other_model)
  {
    $relation = $this->get_relation($label);
    if ($relation['type'] != "N-N")
      Debug::error("Model::map_contains only currently supports N-N relations.");

    $class_name_id = $this->name()."_id";
    $other_class_name = $relation['other'];
    $other_class_name_id = StringHelper::camel_to_underscore($other_class_name)."_id";
    $relation_table = $relation['table'];

    $id = $this->id();
    $other_id = $other_model->id();

    $count = mysql_fetch_array(Database::query("SELECT COUNT(*) FROM $relation_table WHERE `$class_name_id`='$id' AND `$other_class_name_id`='$other_id';"));
    return intval($count[0]) > 0;
  }

  private function get_relation($label)
  {
    if (!isset(static::$relations[$label]))
    {
      Debug::error("Relation $label does not exist.");
      Debug::log(static::$relations);
    }

    return static::$relations[$label];
  }
}
?>
