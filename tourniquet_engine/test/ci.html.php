<style type='text/css'>
  .fail_detail {
    border: 1px solid red;
    padding: 10px;
    margin-bottom: 10px;
  }
</style>

<div id="content-wrap" style="margin:auto">
  <div id="content">
    <div class="button" style="float:right"><a href="?">Revalidate All Tests</a></div>
    <h2>Running all tests...</h2>

    <div id="results"></div>
    <hr />
    <div id="progress">Waiting on <strong><?= TourniquetCI::$total_count ?></strong> test suites to complete...</div>
    <hr />
    <div id="details"></div>
    <hr />
    <div id="success_list"></div>
  </div>
</div>

<script type="text/javascript">
  remaining = <?= TourniquetCI::$total_count ?>;
  total         = 0;
  success_count = 0;
  failed_count  = 0;
  error_count   = 0;
  stack_id      = 1;

  tests_to_run = [];
  <?php foreach(TourniquetCI::$tests as $type => $list) { ?>
    <?php foreach($list as $test) { ?>
      tests_to_run.push({
        type: "<?= $type ?>",
        test: "<?= $test ?>",
        url: url_for("<?= Request::$route_namespace ?>/ci/run/<?= $type ?>/<?= $test ?>.json")
      });
    <?php } ?>
  <?php } ?>

  function report(json, type, test, url) {
    try {
      results = JSON.parse(json);
      results["error"] = 0;
    } catch(err) {
      append_html("details", "<div class='fail_detail'><strong> Error occurred while running " + type + " test suite '" + test + "': </strong>" + json + " <br/><strong>Press ctrl+shift+J to view debug information.</strong> (<a href='" + url + "'>Run Again</a>)</div>");
      append_html("results", "<span style='color:orange'>E</span>");
      results = {
        "success": 0,
        "failure": 0,
        "error": 1,
        "details": [],
        "success_list": []
      }
    }


    new_values = "";
    for(i = 0; i < results["success"]; i++)
      new_values += ".";
    for(i = 0; i < results["failure"]; i++)
      new_values += "<span style='color:red'>F</span>";

    append_html("results", new_values);
    
    for (i = 0; i < results["details"].length; i++) {
      details_html = [];
      for(j = 0; j < results["details"][i].length; j++) {
        message = results["details"][i][j]["message"];
        trace = results["details"][i][j]["stack"];
        details_html.push(message + "(<a href='javascript:show(\"stack" + stack_id + "\")'>Show Stacktrace</a>)<div id='stack" + stack_id + "' style='display:none'>" + trace.join("<br />") + "</div>");
        stack_id += 1;
      }
      append_html("details", "<div class='fail_detail'>" + details_html.join("<br/><br/>") + " (<a href='" + url + "'>Run Again</a>)</div>");
    }

    append_html("success_list", "<span style='color:green'>Running " + type + " test suite '" + test + "':</span> (<a href='" + url + "'>Run Again</a>)<br />");
    for (i = 0; i < results["success_list"].length; i++)
      append_html("success_list", "Running test: <strong>" + results["success_list"][i] + "</strong>... <span style='color:green'>SUCCESS</span><br />");

    success_count += results["success"];
    failed_count  += results["failure"];
    error_count   += results["error"];
    total += results["success"] + results["failure"] + results["error"];

    remaining--;
    if (remaining == 0) {
      set_html("progress", total + " TESTS COMPLETED | <span style='color:green'>SUCCESS: " + success_count + "</span> | <span style='color:red'>FAILED: " + failed_count + "</span> | <span style='color:orange'>ERRORED: " + error_count + "</span>");
    } else {
      set_html("progress", "Waiting on <strong>" + remaining + "</strong> test suites to complete...");
    }
  }

  function run_test_index(i) {
    if (i >= tests_to_run.length) return;

    remote_call(tests_to_run[i].url, {}, function(result) {
      report(result, tests_to_run[i].type, tests_to_run[i].test, tests_to_run[i].url);
      run_test_index(i+1);
    }, function(status, result) {
      report(result, tests_to_run[i].type, tests_to_run[i].test, tests_to_run[i].url);
      run_test_index(i+1);
    });
  }

  run_test_index(0);
</script>
