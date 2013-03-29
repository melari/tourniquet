<head>
  <?php $this->content_for_header(); ?>
</head>
<?php if ($message = Flash::message()) { ?>
  <strong><?php echo($message); ?></strong>
<?php } ?>
<br/><br/>
<?php $this->show_view(); ?>
