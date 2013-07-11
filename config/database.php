<?php
class Database
{
  private static $host = "localhost";
  private static $user = "root";
  private static $password = "";
  private static $database_name = "my_app";
  private static $sql_connection;

  private static $query_count = 0;

  public static function select_credentials($options)
  {
    /* Select credentials based on server and environment */
    if (Config::$env == "production" && Config::$server == "main")
    {
      self::$user = "root";
      self::$password = "password";
      self::$database_name = "production_db";
    }
  }

  public static function open_connection($options = array())
  {
    self::select_credentials($options);
    self::$sql_connection = mysql_connect(self::$host, self::$user, self::$password)
      or Debug::error("[Tourniquet] FATAL: Could not connect to database server.");
    mysql_select_db(self::$database_name, self::$sql_connection);
    mysql_set_charset("utf8");
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
}
