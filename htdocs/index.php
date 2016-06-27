<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
try {
  include '../tourniquet_engine/tourniquet.php';
  Tourniquet::start();
} catch(Exception $e) {
  $filtered_params = Request::$params;
  if (isset($filtered_params['password']))
    $filtered_params['password'] = 'REDACTED';
  $filtered_cookies = $_COOKIE;
  if (isset($filtered_cookies['PHPSESSID']))
    $filtered_cookies['PHPSESSID'] = 'REDACTED';
  if (isset($filtered_cookies['rt']))
  $filtered_cookies['rt'] = 'REDACTED';
?>

<head>
  <style type="text/css">
    body { background-color: #aee; color: #333 }
    textarea { width: 100%; height: 400px; }
  </style>
</head>

<body>
  <h1>Oops! Something went very wrong.</h1>

  <strong>Share this error message with the project maintainers to help get things sorted out:</strong>

  <p><textarea>
    [<?= Time::now() ?> on <?= Config::$server ?>::<?= Config::$env ?>_v<?= Config::$version ?>] <?= Request::$method ?>: <?= Request::full_request_uri() ?>
    &#10;
    Params: <?php print_r($filtered_params) ?>
    Cookies: <?php print_r($filtered_cookies) ?>
    <?= $e ?>
  </textarea></p>
</body>

<?php Debug::flush_to_console() ?>

<?php } ?>
