<?php
class Model
{
  public $validation_errors = array();       # Holds the last validation errors that occured.

  protected static $attributes = array();           # A list of custom attributes that are loaded in the constructor (to be overriden)
  protected static $protected_attributes = array(); # A list of attributes that cannot be set using mass assignment. (to be overriden)
  protected static $table = "";              # Name of the corresponding database table (to be overridden)

  private $attr = array("id" => null);       # Model Attribute
  private $in_database = false;              # Keeps track if this model is saved to the database yet or not.
  private $last_saved_id;                    # The ID of this model as it is currently saved in the database (since $attr[id] might change)
  protected static $relations = null;              # Stores this model's relations.

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

    if ($args != null)
    {
      if (is_numeric($args))
        $this->load_by_id($args);
      if (is_array($args))
        $this->update_from_params($args);
    }

    if (static::$relations == null)
      $this->mappings();
  }

  public function as_json()
  {
    return json_encode($this->attr);
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
    return $this->attr[$name];
  }

  /**
   * Simple setter shortcut for setting a model's attributes.
  **/
  public function set($name, $value)
  {
    $this->attr[$name] = $value;
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

    $command = $this->in_database ? "UPDATE" : "INSERT INTO";
    $query = $command." `".$this->table_name()."` SET";
    $first = true;
    foreach($this->attr as $attribute => $value)
    {
      if ($command == "UPDATE" && $attribute == "id")
        continue;

      if (!$first)
        $query .= ",";
      $query .= " `".$attribute."`='".Database::sanitize($value)."'";
      $first = false;
    }

    if ($command == "UPDATE")
      $query .= " WHERE `id`='".Database::sanitize($this->id())."'";

    return Database::query($query) !== false;
  }

  /** Removes this model from the database. **/
  public function destroy()
  {
    if ($this->in_database)
      Database::query("DELETE FROM `".$this->table_name()."` WHERE id='".$this->last_saved_id."'");
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

  /**
   * Searches the database using the given parameters, returning an array of model
   *  objects corresponding to the results.
  **/
  public static function find($params, $conditions = array())
  {
    $result_array = array();

    $query = sprintf("SELECT * FROM `%s`", self::table_name());
    if (count($params) > 0)
    {
      $query .= " WHERE";
      $first = true;
      foreach($params as $name => $value)
      {
        if (!$first)
          $query .= " AND ";
        else
          $query .= " ";
        $query .= sprintf("`%s`='%s'", Database::sanitize($name), Database::sanitize($value));
      }
    }

    if (isset($conditions["order_by"]))
      $query .= " ORDER BY " . Database::sanitize($conditions["order_by"]);
    if (isset($conditions["limit"]))
      $query .= " LIMIT " . Database::sanitize($conditions["limit"]);
    if (isset($conditions["offset"]))
      $query .= " OFFSET " . Database::sanitize($conditions["offset"]);

    $result = Database::query($query);
    while($row = mysql_fetch_array($result))
    {
      $new_model = new static();
      $new_model->create_from_table_result($row);
      array_push($result_array, $new_model);
    }

    return $result_array;
  }

  /**
   * Searches the database using the given raw SQL as the where clause.
   * WARNING: the user is responsible for protecting against SQL injection in this
   * case!! Be sure to make use of Database::sanitize()
  **/
  public static function query($query)
  {
    $result_array = array();
    $result = Database::query(sprintf("SELECT * FROM `%s` WHERE %s", self::table_name(), $query));
    while($row = mysql_fetch_array($result))
    {
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
      $this->attr[$attribute] = $params[$attribute];
    }
  }

  /** ======== VALIDATION HELPER FUNCTIONS ======== **/
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
    $query = "SELECT COUNT(*) FROM `".$this->table_name()."` WHERE `".$attribute."`='".Database::sanitize($this->get($attribute))."'";
    if ($this->in_database)
      $query .= "AND id<>'".$this->last_saved_id."'";

    if (Database::count_query($query) > 0)
      $this->add_validation_error($error, $attribute." must be unique.");
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

  public function get_map($label)
  {
    if (!isset(static::$relations[$label]))
      Debug::error("Relation $label does not exist.");

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
      return $other_class_name::find(array($class_name_id_or_custom => $this->id()));
      break;
    case 'N-N':
      $my_id = $this->id();
      if ($my_id == "") Debug::error("Cannot follow N-N mapping without an ID");
      return $other_class_name::query("`id` IN (SELECT `$other_class_name_id` FROM `$relation_table` WHERE `$class_name_id`='$my_id')");
      break;
    }
  }
}
?>