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

    self::$sql_connection = mysql_connect(self::$host, self::$user, self::$password)
      or Debug::error("[Tourniquet] FATAL: Could not connect to database server.");
    mysql_select_db(self::$database_name, self::$sql_connection);
    mysql_set_charset('utf8');
  }

  public static function query($query, $debug = false)
  {
    self::$query_count++;
    if ($debug || Config::$env == "test" || Config::$env == "debug")
      Debug::log(sprintf("[Database Query][%d] %s", self::$query_count, $query), '#2CBFA2');
    $result = mysql_query($query);
    if (!$result && (Config::$env == "test" || Config::$env == "debug"))
    {
      Debug::error(sprintf("[Database Query Error] %s | %s", $query, mysql_error()));
      Debug::flush_to_console();
    }

    if (!$result && self::$raise_on_error)
    {
      throw new Exception(mysql_error());
    }

    return $result;
  }

  public static function query_to_array($query)
  {
    $query_result = self::query($query);
    $result = array();
    while($row = mysql_fetch_array($query_result)) {
      array_push($result, $row);
    }
    return $result;
  }

  public static function sanitize($text)
  {
    if (is_bool($text))
      $text = $text ? '1' : '0';
    return mysql_real_escape_string($text);
  }

  public static function count_query($query, $debug = false)
  {
    $row = mysql_fetch_array(self::query($query, $debug));
    return $row[0];
  }

  public static function kill_connection()
  {
    if (self::$sql_connection != null)
    {
      mysql_close(self::$sql_connection);
    }
  }
}
