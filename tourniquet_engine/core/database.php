<?php
class Database
{
  public static $enabled = true;
  public static $host = "localhost";
  public static $user = "root";
  public static $password = "";
  public static $database_name = "my_app";
  private static $sql_connection;
  private static $query_count = 0;
  public static $raise_on_error = false;

  public static function open_connection()
  {
    self::kill_connection();
    include Router::path_for('config/database.php');
    if (!self::$enabled) { return; }

    try {
      self::$sql_connection = mysqli_connect(self::$host, self::$user, self::$password, self::$database_name);
    } catch(Exception $e) {
      throw new ErrorException(
        $e->getMessage()
      );
    }
    mysqli_set_charset(self::$sql_connection, 'utf8');
  }

  public static function query($query, $debug = false)
  {
    self::$query_count++;
    if ($debug || Config::$env == "test" || Config::$env == "debug")
      Debug::log(sprintf("[Database Query][%d] %s", self::$query_count, $query), '#2CBFA2');
    $result = mysqli_query(self::$sql_connection, $query);

    if (!$result)
    {
      $error = sprintf("[Database Query Error] %s | %s", $query, mysqli_error(self::$sql_connection));
      if (Config::$env == "debug")
      {
        Debug::error($error);
        Debug::flush_to_console();
      }
      if (self::$raise_on_error)
      {
        throw new Exception($error);
      }
    }

    return $result;
  }

  public static function query_to_array($query)
  {
    $query_result = self::query($query);
    $result = array();
    while($row = mysqli_fetch_assoc($query_result)) {
      array_push($result, $row);
    }
    return $result;
  }

  public static function sanitize($text)
  {
    if (is_bool($text))
      $text = $text ? '1' : '0';
    return mysqli_real_escape_string(self::$sql_connection, $text);
  }

  public static function count_query($query, $debug = false)
  {
    $row = mysqli_fetch_assoc(self::query($query, $debug));
    return $row['COUNT(*)'];
  }

  public static function kill_connection()
  {
    if (self::$sql_connection != null)
    {
      mysqli_close(self::$sql_connection);
    }
  }

  public static function insert_id()
  {
    return mysqli_insert_id(self::$sql_connection);
  }

  public static function fetch_assoc($result)
  {
    return mysqli_fetch_assoc($result);
  }

  public static function num_rows($result)
  {
    return mysqli_num_rows($result);
  }
}
