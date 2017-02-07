<?php
class Model
{
  public $validation_errors = array();       # Holds the last validation errors that occured.

  protected static $attributes = array();           # A list of custom attributes that are loaded in the constructor (to be overriden)
  protected static $readonly_attributes = array("created_at", "updated_at");  # A list of attributes that are loaded from the database, but never saved.
  protected static $protected_attributes = array(); # A list of attributes that cannot be set using mass assignment. (to be overriden)
  protected static $time_attributes = array(); # A list of attributes that should be converted to Time objects
  protected static $table = "";              # Name of the corresponding database table (to be overridden)

  private $attr = array("id" => null);       # Model Attribute
  private $dirty_attr= array();
  private $readonly_attr = array();
  private $in_database = false;              # Keeps track if this model is saved to the database yet or not.
  private $last_saved_id;                    # The ID of this model as it is currently saved in the database (since $attr[id] might change)
  protected static $relations = null;              # Stores this model's relations.

  protected static $scopes = null;           # Stores this model's scopes

  public static function array_to_json($model_array, $whitelist = null, $blacklist = null)
  {
    $result = array();
    foreach($model_array as $model)
      array_push($result, $model->as_json());

    if ($whitelist)
      for($i=0; $i < count($result); $i++)
        foreach($result[$i] as $attribute => $value)
          if (!in_array($attribute, $whitelist))
            unset($result[$i][$attribute]);

    if ($blacklist)
      for($i=0; $i < count($result); $i++)
        foreach($result[$i] as $attribute => $value)
          if (in_array($attribute, $blacklist))
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
  function __construct($args = null, $ignore_protected = false)
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
        $this->update_from_params($args, $ignore_protected);
    }

    if (static::$relations == null)
      $this->mappings();
    if (static::$scopes == null)
      $this->scopes();
  }

  public function is_saved()
  {
    return $in_database;
  }

  public function as_json()
  {
    return array_merge($this->attr, $this->readonly_attr);
  }

  public function name()
  {
    return StringHelper::camel_to_underscore(get_class($this));
  }

  public function has_attribute($attribute)
  {
    return in_array($attribute, static::$attributes);
  }

  /** Shortcut for getting the id of this model. **/
  public function id($default = null)
  {
    $id = $this->attr["id"];
    return $id ? $id : $default;
  }

  /**
   * Simple accessor shortcut for getting a models attributes.
  **/
  public function get($name)
  {
    $value = isset($this->attr[$name]) ? $this->attr[$name] : $this->readonly_attr[$name];
    if (in_array($name, static::$time_attributes))
      return Time::parse($value, Time::$database_timezone);
    return $value;
  }

  /**
   * Simple setter shortcut for setting a model's attributes.
  **/
  public function set($name, $value)
  {
    if (in_array($name, static::$time_attributes))
    {
      if (is_string($value)) { $value = Time::parse($value); }
      $value = $value->format(Time::$database_format, Time::$database_timezone);
    }
    if ($this->attr[$name] === $value) return;
    $this->attr[$name] = $value;
    $this->mark_as_dirty($name);
  }

  private function mark_as_dirty($attribute_name)
  {
    if (!in_array($attribute_name, $this->dirty_attr))
      array_push($this->dirty_attr, $attribute_name);
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

    if (count($this->dirty_attr) == 0)
    {
      if ($this->in_database)
        return true;
      else
        $this->dirty_attr = array('id');
    }

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
      $this->load_by_id(Database::insert_id());
      $this->dirty_attr = array();
      return true;
    }
    return false;
  }

  public static function update_all($updates, $params = array())
  {
    if ($params === true)
      $params = array();
    else if (count($params) == 0)
      return Debug::warn("Calling Model::update_all with an empty params hash will update all records in the database. If this is the intended action, call with Model::update_all(_, true)");

    $update_string = "";
    $first = true;
    foreach($updates as $attribute => $value)
    {
      if (!$first)
        $update_string .= ", ";
      $update_string .= " `$attribute`='".Database::sanitize($value)."'";
      $first = false;
    }

    $where_query = self::where_query($params, array());
    $query = "UPDATE `".self::table_name()."` SET$update_string";
    if ($where_query != "")
      $query .= " WHERE$where_query";
    Database::query($query);
  }

  public static function destroy_all($params = array(), $use_callbacks = false)
  {
    if ($params === true)
      $params = array();
    else if (count($params) == 0)
      return Debug::warn("Calling Model::destroy_all with an empty params hash will delete all records from the database. If this is the intended action, call with Model::destroy_all(true)");

    $use_callbacks ? self::destroy_all_with_callbacks($params) : self::destroy_all_without_callbacks($params);
  }

  private static function destroy_all_with_callbacks($params)
  {
    foreach(self::find($params) as $model)
      $model->destroy_callbacks();
    self::destroy_all_without_callbacks($params);
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
    if (isset($conditions["force_index"]))
    {
      $query .= sprintf(" FORCE INDEX (`%s`)", $conditions["force_index"]);
    }
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
    while($row = Database::fetch_assoc($result))
    {
      if ($count)
        return intval($row["COUNT(*)"]);

      $new_model = new static();
      $new_model->create_from_table_result($row);
      array_push($result_array, $new_model);
    }

    return $result_array;
  }

  public static function where_query($params, $conditions)
  {
    if ($conditions['use_raw']) { return ' ' . $params['raw']; }

    $first = true;
    $where_query = "";
    foreach($params as $name => $value)
    {
      if ($value == null && $conditions["exclude_null"]) continue;

      if (!$first)
        $where_query .= " AND ";
      else
        $where_query .= " ";

      if (StringHelper::ends_with($name, " (LIKE)"))
        $where_query .= sprintf("`%s` LIKE '%s'", Database::sanitize(substr($name, 0, -7)), Database::sanitize($value));
      else if (StringHelper::ends_with($name, " (%LIKE%)"))
        $where_query .= sprintf("`%s` LIKE '%%%s%%'", Database::sanitize(substr($name, 0, -9)), Database::sanitize($value));
      else if (StringHelper::ends_with($name, " (LIKE%)"))
        $where_query .= sprintf("`%s` LIKE '%s%%'", Database::sanitize(substr($name, 0, -8)), Database::sanitize($value));
      else if (StringHelper::ends_with($name, " (IN)"))
        $where_query .= sprintf('`%s` IN (%s)', Database::sanitize(substr($name, 0, -5)), $value);
      else if (StringHelper::ends_with($name, " (NOT IN)"))
        $where_query .= sprintf('`%s` NOT IN (%s)', Database::sanitize(substr($name, 0, -9)), $value);
      else if (StringHelper::ends_with($name, " (LOWER)"))
        $where_query .= sprintf("LOWER(`%s`) = '%s'", Database::sanitize(substr($name, 0, -8)), Database::sanitize($value));
      else if (StringHelper::ends_with($name, " (>)"))
        $where_query .= sprintf("`%s`>'%s'", Database::sanitize(substr($name, 0, -4)), Database::sanitize($value));
      else if (StringHelper::ends_with($name, " (<)"))
        $where_query .= sprintf("`%s`<'%s'", Database::sanitize(substr($name, 0, -4)), Database::sanitize($value));
      else if ($value === null)
        $where_query .= sprintf("`%s` IS NULL", Database::sanitize($name));
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
    while($row = Database::fetch_assoc($result))
    {
      if ($selection == "COUNT(*)")
        return intval($row["COUNT(*)"]);

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
    if ($row = Database::fetch_assoc($result))
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
  public function update_from_params($params, $ignore_protected = false)
  {
    foreach($this->attr as $attribute => $value)
    {
      if (in_array($attribute, static::$protected_attributes) && !$ignore_protected)
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

  protected function validate_value_of($attribute, $values, $error = "")
  {
    if (!in_array($this->get($attribute), $values))
      $this->add_validation_error($error, $attribute." has an value this is not permitted.");
  }

  public function add_validation_error($custom_error, $fallback)
  {
    array_push($this->validation_errors, $custom_error == "" ? $fallback : $custom_error);
  }

  /** ========= MAPPING HELPER FUNCTION ========== **/
  public function mappings() { }

  protected function map($label, $type, $other_name, $options = array())
  {
    // DEPRECIATED:
    // Passing a single string into options, previously acted as the map table
    // name for N-N relations, and as the foreign key name for other relations.
    if (is_string($options))
    {
      Debug::warn("DEPRECIATED: $label mapping is using outdated option call");
      Debug::flush_to_console();
      exit;
    }

    $table_name = $options['table_name'];
    if ($table_name == null && $type == "N-N") {
      Debug::error("Relation $label of N-N type must have a table_name.");
      return;
    }
    if ($table_name != null && $type != "N-N") {
      Debug::error("Relation $label of $type type cannot have an explicit table_name.");
      return;
    }

    $reference_column_name = $options['reference_column_name'];
    if ($reference_column_name == null) {
      if ($type == '1!-1' || $type == '1-N') {
        $reference_column_name = StringHelper::camel_to_underscore($other_name) . "_id";
      } else {
        $reference_column_name = $this->name() . "_id";
      }
    }

    $other_reference_column_name = $options['other_reference_column_name'];
    if ($other_reference_column_name == null && $type == "N-N") {
      $other_reference_column_name = StringHelper::camel_to_underscore($other_name) . "_id";
    }
    if ($other_reference_column_name != null && $type != "N-N") {
      Debug::error("Relation $label of $type type cannot have a other_reference_column_name");
      return;
    }

    static::$relations[$label] = array(
      'type' => $type,
      'other_class_name' => $other_name,
      'table_name' => $table_name,
      'reference_column_name' => $reference_column_name,
      'other_reference_column_name' => $other_reference_column_name
    );
  }

  public function get_map($label, $conditions = array())
  {
    if (!isset(static::$relations[$label]))
    {
      Debug::error("Relation $label does not exist.");
      Debug::log(static::$relations);
    }

    $relation = static::$relations[$label];
    $relation_table = $relation['table_name'];
    $other_class_name = $relation['other_class_name'];
    $reference_column = $relation['reference_column_name'];
    $other_reference_column = $relation['other_reference_column_name'];

    switch($relation['type'])
    {
    case '1!-1':
      $other = new $other_class_name($this->get($reference_column));
      if ($other->id() == null) return null;
      return $other;
      break;
    case '1-1!':
      if ($this->id() == "") Debug::error("Cannot follow 1-1! mapping without an ID");
      return $other_class_name::find_one(array($reference_column => $this->id()));
      break;
    case '1-N':
      return new $other_class_name($this->get($reference_column));
      break;
    case 'N-1':
      if ($this->id() == "") Debug::error("Cannot follow N-1 mapping without an ID");
      return $other_class_name::find(array($reference_column => $this->id()), $conditions);
      break;
    case 'N-N':
      $my_id = $this->id();
      if ($my_id == "") Debug::error("Cannot follow N-N mapping without an ID");
      return $other_class_name::query("`id` IN (SELECT `$other_reference_column` FROM `$relation_table` WHERE `$reference_column`='$my_id')", $conditions);
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
    $type = $relation['type'];
    $relation_table = $relation['table_name'];
    $other_class_name = $relation['other_class_name'];
    $reference_column = $relation['reference_column_name'];
    $other_reference_column = $relation['other_reference_column_name'];

    if ($type != "N-N")
      Debug::error("Model::add_map can only be used with N-N relations.");

    $my_id = $this->id();
    $other_id = $other_model->id();
    $existing = Database::fetch_assoc(Database::query("SELECT COUNT(*) FROM $relation_table WHERE `$reference_column`='$my_id' AND `$other_reference_column`='$other_id' LIMIT 1"));
    if (intval($existing[0]) > 0)
      return false;
    Database::query("INSERT INTO $relation_table (`$reference_column`, `$other_reference_column`) VALUES('$my_id', '$other_id');");
    return true;
  }

  public function remove_map($label, $other_model)
  {
    $relation = $this->get_relation($label);
    if ($relation['type'] != "N-N")
      Debug::error("Model::remove_map can only be used with N-N relations.");

    $relation = static::$relations[$label];
    $type = $relation['type'];
    $relation_table = $relation['table_name'];
    $other_class_name = $relation['other_class_name'];
    $reference_column = $relation['reference_column_name'];
    $other_reference_column = $relation['other_reference_column_name'];

    $my_id = $this->id();
    $other_id = $other_model->id();
    Database::query("DELETE FROM $relation_table WHERE `$reference_column`='$my_id' AND `$other_reference_column`='$other_id';");
  }

  public function remove_all_map($label)
  {
    $relation = $this->get_relation($label);
    if ($relation['type'] != "N-N")
      Debug::error("Model::remove_all_map can only be used with N-N relations.");

    $id = $this->id();
    $relation_table = $relation['table_name'];
    $reference_column = $relation['reference_column_name'];
    Database::query("DELETE FROM $relation_table WHERE `$reference_column`='$id';");
  }

  public function map_contains($label, $other_model)
  {
    $relation = $this->get_relation($label);
    if ($relation['type'] != "N-N")
      Debug::error("Model::map_contains only currently supports N-N relations.");

    $type = $relation['type'];
    $relation_table = $relation['table_name'];
    $other_class_name = $relation['other_class_name'];
    $reference_column = $relation['reference_column_name'];
    $other_reference_column = $relation['other_reference_column_name'];

    $id = $this->id();
    $other_id = $other_model->id();

    return Database::count_query("SELECT COUNT(*) FROM $relation_table WHERE `$reference_column`='$id' AND `$other_reference_column`='$other_id';") > 0;
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
