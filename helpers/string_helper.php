<?php
class StringHelper
{
  static function contains($haystack, $needle)
  {
    return strpos(strtolower($haystack), strtolower($needle)) !== false;
  }
  
  static function starts_with($haystack, $needle)
  {
    return !strncmp($haystack, $needle, strlen($needle));
  }
  
  static function ends_with($haystack, $needle)
  {
    $length = strlen($needle);
    if ($length == 0) return true;
    return (substr($haystack, -$length) === $needle);
  } 

  static function camel_to_underscore($str)
  {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
  }

  static function underscore_to_camel($str)
  {
    $words = explode('_', strtolower($str));
    $result = '';
    foreach ($words as $word) {
      $result .= ucfirst(trim($word));
    }
    return $result;
  }
}
?>
