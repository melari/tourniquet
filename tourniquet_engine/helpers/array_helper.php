<?php
class ArrayHelper
{
  public static function map($array, $lambda)
  {
    $result = array();
    foreach($array as $element)
      array_push($result, $lambda($element));
    return $result;
  }

  public static function map_with_context($array, $context, $lambda)
  {
    $result = array();
    foreach($array as $element)
      array_push($result, $lambda($element, $context));
    return $result;
  }

  public static function filter($array, $lambda)
  {
    $result = array();
    foreach($array as $element)
    {
      if ($lambda($element))
        array_push($result, $element);
    }
    return $result;
  }

  public static function filter_with_context($array, $context, $lambda)
  {
    $result = array();
    foreach($array as $element)
    {
      if ($lambda($element, $context))
        array_push($result, $element);
    }
    return $result;
  }

  public static function includes($array, $lambda)
  {
    foreach($array as $element)
      if ($lambda($element)) return true;
    return false;
  }

  public static function includes_with_context($array, $context, $lambda)
  {
    foreach($array as $element)
      if ($lambda($element, $context)) return true;
    return false;
  }

  public static function is_assoc_array($array)
  {
    if (!is_array($array))
      return false;
    $keys = array_keys($array);
    return $keys[0] != "0";
  }
}
?>
