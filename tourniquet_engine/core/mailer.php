<?php
class Mailer
{
  public $html = true;
  public $to = null;
  public $from = "no-reply@my_application.com";
  public $subject = "";
  public $message = "";

  public function __construct($options = array())
  {
    if (isset($options["to"]))
      $this->to = $options["to"];
    if (isset($options["from"]))
      $this->from = $options["from"];
    if (isset($options["html"]))
      $this->html = $options["html"];
    $this->create($options);
  }

  protected function create($options) { }

  public function send()
  {
    if ($this->to == null)
      return Debug::error("Cannot send message. 'TO' is not set.");

    $header = "From: " . $this->from . PHP_EOL;
    if ($this->html)
      $header .= "Content-type: text/html" . PHP_EOL;

    if (mail($this->to, $this->subject, $this->message, $header))
      return true;

    Debug::error("Email to $this->to failed to send.");
    return false;
  }
}
?>
