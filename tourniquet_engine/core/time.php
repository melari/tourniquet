<?php
class Time
{
  public static $database_timezone = 'UTC';
  public static $database_format = 'Y-m-d H:i:s';
  public static $default_display_timezone = 'UTC';
  public static $default_display_format = 'Y-m-d h:i:s A T';
  private $unixtime;
  private $display_timezone;
  private $display_format;

  function __construct($unixtime, $display_timezone = null, $display_format = null)
  {
    $this->unixtime = $unixtime;
    $this->display_timezone = $display_timezone;
    $this->display_format   = $display_format;

    if ($this->display_timezone == null)
      $this->display_timezone = self::$default_display_timezone;
    if ($this->display_format == null)
      $this->display_format = self::$default_display_format;
  }

  public static function with_timezone($zone, $method, $context = null)
  {
    $ref = date_default_timezone_get();
    date_default_timezone_set(strval($zone));
    $result = $method($context);
    date_default_timezone_set($ref);
    return $result;
  }

  public static function parse($string, $timezone = null)
  {
    if ($timezone == null)
      $timezone = static::$default_display_timezone;
    return self::with_timezone($timezone, function($string) {
      return new Time(strtotime($string));
    }, $string);
  }

  public static function now()
  {
    return new Time(time());
  }

  public static function seconds($amount)
  {
    return new Time($amount);
  }

  public static function minutes($amount)
  {
    return new Time($amount * 60);
  }

  public static function hours($amount)
  {
    return new Time($amount * 3600);
  }

  public static function days($amount)
  {
    return new Time($amount * 86400);
  }

  public static function weeks($amount)
  {
    return new Time($amount * 604800);
  }

  public static function months($amount)
  {
    return new Time($amount * 2592000);
  }

  public static function years($amount)
  {
    return new Time($amount * 31556952);
  }

  public function ago()
  {
    return Time::now()->minus($this);
  }

  public function from_now()
  {
    return Time::now()->plus($this);
  }

  public function plus($other)
  {
    return $this->dup()->mutate_time($this->unixtime + $other->unixtime);
  }

  public function minus($other)
  {
    return $this->dup()->mutate_time($this->unixtime - $other->unixtime);
  }

  public function dup()
  {
    return new Time($this->unixtime, $this->display_timezone, $this->display_format);
  }

  public function mutate_time($new_time)
  {
    $this->unixtime = $new_time;
    return $this;
  }

  public function unix()
  {
    return $this->unixtime;
  }

  public function display_zone()
  {
    return $this->display_timezone;
  }

  public function display_format()
  {
    return $this->display_format;
  }

  public function format($format = null, $timezone = null)
  {
    if ($format == null)
      $format = $this->display_format;
    if ($timezone == null)
      $timezone = $this->display_timezone;

    return Time::with_timezone($timezone, function($format) {
      return date($format, $this->unixtime);
    }, $format);
  }

  public function relative_format($granularity = 2)
  {
    if ($timezone == null)
      $timezone = $this->display_timezone;
    $interval = Time::now()->minus($this)->unix();
    if ($interval < 60)
      return 'just now';

    $units = array('1 year|@count years' => 31556952, '1 week|@count weeks' => 604800, '1 day|@count days' => 86400, '1 hour|@count hours' => 3600, '1 min|@count min' => 60, '1 sec|@count sec' => 1);
    $output = '';
    foreach ($units as $key => $value) {
      $key = explode('|', $key);
      if ($interval >= $value) {
        $floor = floor($interval / $value);
        $output .= ($output ? ', ' : '') . ($floor == 1 ? $key[0] : str_replace('@count', $floor, $key[1]));
        $interval %= $value;
        $granularity--;
      }

      if ($granularity == 0) {
        break;
      }
    }

    return ($output ? $output : '0 sec') . ' ago';
  }

  public function __toString()
  {
    return $this->format();
  }
}
?>
