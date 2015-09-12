<h3>Tourniquet CI</h3>

<div id="results"></div>
<hr />
<div id="details"></div>
<hr />
<a href="javascript:show('success_list')">Show full details</a>
<div id="success_list" style="display:none"></div>

<script type="text/javascript">
  remaining = <?= TourniquetCI::$total_count ?>;
  total         = 0;
  success_count = 0;
  failed_count  = 0;

  <?php foreach(TourniquetCI::$tests as $type => $list) { ?>
    <?php foreach($list as $test) { ?>
      remote_call(url_for("<?= Request::$route_namespace ?>/ci/run/<?= $type ?>/<?= $test ?>.json"), {}, function(result) {
        report(JSON.parse(result));
      });
    <?php } ?>
  <?php } ?>

  function report(results) {
    new_values = "";
    for(i = 0; i < results["success"]; i++)
      new_values += ".";
    for(i = 0; i < results["failure"]; i++)
      new_values += "<span style='color:red'>F</span>";

    append_html("results", new_values);
    
    for (i = 0; i < results["details"].length; i++)
      append_html("details", "<div class='fail_detail'>" + results["details"][i] + "</div>");

    for (i = 0; i < results["success_list"].length; i++)
      append_html("success_list", "Running test: <strong>" + results["success_list"][i] + "</strong>... <span style='color:green'>SUCCESS</span><br />");

    success_count += results["success"];
    failed_count  += results["failure"];
    total += results["success"] + results["failure"];

    remaining--;
    if (remaining == 0) {
      append_html("results", "<hr />" + total + " TEST COMPLETED | <span style='color:green'>SUCCESS: " + success_count + "</span> | <span style='color:red'>FAILED: " + failed_count + "</span>");
    }
  }
</script>
