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

  static function format_date($type, $date)
  {
    switch ($type)
    {
      case "full":
        $format_string = "l jS \of F Y h:i:s A";
        break;

      case "short":
        $format_string = "Y-m-d h:i:s A";
        break;
    }
    return date($format_string, strtotime($date));
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
      $result = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", str_replace("\n", "<br />", htmlspecialchars($result)));

    return $result;
  }

  // =============================================================
  // For use when embedding a php string into a javascript string.
  // =============================================================
  static function escape_for_javascript($value)
  {
    return str_replace("\"", "\\\"", str_replace("'", "\\'", $value));
  }

  // ==============================================
  // For use when embedding a php string into html.
  // ==============================================
  static function escape_for_html($value)
  {
    return htmlentities($value, ENT_QUOTES);
  }

  // ==============================================
  // Converts a mobile plaintext content to HTML
  // ==============================================
  static function plaintext_to_html($value)
  {
    return str_replace("\n", "<br />", $value);
  }

  // =====================================================
  // Converts HTML content to plaintext for mobile editing
  // =====================================================
  static function html_to_plaintext($value)
  {
    return strip_tags(str_replace("<br>", "\n", $value), "<hr><strong><i><u><sup><sub><span><em><ul><ol><li><a><img><video><audio>");
  }
}
?>
