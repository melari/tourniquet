<?php
class Debug
{
  private static $buffer = "";
  public static function log($message, $color = 'black')
  {
    if (is_array($message) || is_object($message))
      $message = print_r($message, true);
    self::$buffer .= "<script type='text/javascript'>console.log(\"%c \"+".json_encode($message).", 'color:$color');</script>";
  }

  public static function warn($message)
  {
    self::$buffer .= "<script type='text/javascript'>console.warn(".json_encode($message).");</script>";
  }

  public static function error($message)
  {
    self::$buffer .= "<script type='text/javascript'>console.error(".json_encode($message).");</script>";
  }

  public static function flush_to_console()
  {
    echo(self::$buffer);
    self::$buffer = "";
  }
}
?>
