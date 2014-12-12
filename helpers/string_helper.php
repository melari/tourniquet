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

  static function underscore_to_human($str)
  {
    $words = explode('_', strtolower($str));
    return self::lambda_join(' ', $words, function($word) {
      return ucfirst(trim($word));
    });
  }

  static function lambda_join($delim, $collection, $lambda)
  {
    return self::lambda_join_with_context($delim, $collection, null, $lambda);
  }

  static function lambda_join_with_context($delim, $collection, $context, $lambda)
  {
    $result = "";
    foreach($collection as $element)
    {
      if ($context == null)
        $result .= $lambda($element);
      else
        $result .= $lambda($element, $context);
      if ($element !== end($collection))
        $result .= $delim;
    }
    return $result;
  }

  static function json_pretty_print($json, $use_html_breaks = false)
  {
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen( $json );

    for( $i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if( $ends_line_level !== NULL ) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ( $in_escape ) {
            $in_escape = false;
        } else if( $char === '"' ) {
            $in_quotes = !$in_quotes;
        } else if( ! $in_quotes ) {
            switch( $char ) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ( $char === '\\' ) {
            $in_escape = true;
        }
        if( $new_line_level !== NULL ) {
            $result .= "\n".str_repeat( "\t", $new_line_level );
        }
        $result .= $char.$post;
    }

    if ($use_html_breaks)
      $result = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", str_replace("\n", "<br />", $result));

    return $result;
  }
}
?>
