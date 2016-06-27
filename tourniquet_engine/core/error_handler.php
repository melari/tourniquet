<?php
function exception_error_handler($severity, $message, $file, $line) {
  if (!(error_reporting() & $severity)) {
    // This error code is not included in error_reporting
    return;
  }

  throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

function check_for_fatal()
{
  $error = error_get_last();
  if ($error['type'] == E_ERROR)
    exception_error_handler(E_ERROR, $error['message'], $error['file'], $error['line']);
}
register_shutdown_function("check_for_fatal");
?>
