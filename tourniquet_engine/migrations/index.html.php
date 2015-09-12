<div id="content">
  <h2>Pending Migrations</h2>
  <div class="clear"></div>
  <?php if (count(Migration::pending()) > 0) { ?>
    <ul>
      <?php foreach(Migration::pending() as $m) { ?> 
      <li><?= $m->get("sha"); ?>: <?= $m->name ?></li>
      <?php } ?>
    </ul>
  <?php } else { ?>
    <strong>Schema is up to date.</strong>
  <?php } ?>

  <a class="button" href="<?= Router::url_with_namespace_for("/migrations/run?__debug__") ?>">Run all</a>
</div>

<div id="content">
  <h2>Completed Migrations</h2>
  <div class="clear"></div>

  <ul>
    <?php foreach(Migration::completed() as $m) { ?>
      <li>
        <?= $m->get("sha"); ?>: <?= $m->name ?>
        (<a href="<?= Router::url_with_namespace_for("/migrations/revert/" . $m->get("sha") . "?__debug__") ?>">Revert</a>)
      </li>
    <?php } ?>
  </ul>
</div>
