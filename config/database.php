<?php
class Database
{
  private static $host = "localhost";
  private static $user = "root";
  private static $password = "";
  private static $database_name = "my_app";
  private static $sql_connection;

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
  }

  public static function query($query, $debug = false)
  {
    if ($debug || Config::$env == "test" || Config::$env == "debug")
      Debug::log(sprintf("[Database Query] %s", $query), '#2CBFA2');
    return mysql_query($query);
  }

  public static function sanitize($text)
  {
    return mysql_real_escape_string($text);
  }

  public static function count_query($query, $debug = false)
  {
    $row = mysql_fetch_array(self::query($query, $debug));
    return $row[0];
  }
}
