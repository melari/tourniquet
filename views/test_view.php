Editing user <?php echo($this->user->get('name')); ?>
<br />
<?php $this->form_for($this->user, "/user/view/".$this->user->id()); ?>
  <?php $this->date_input("name"); ?>
  <?php $this->text_area("tagline"); ?>
  <?php $this->check_box("background_loop"); ?>
  <input type="submit" value="Save" />
<?php $this->end_form(); ?>

<br/><br/><br/>

<input type="text" id="search_box" onkeyup="debounce('unique_id', do_search)" />
<script type="text/javascript">
  function do_search()
  {
    remote_call('/post/view.json', remote_callback_test, {"user_id": document.getElementById('search_box').value})
  }

  function remote_callback_test(result)
  {
    var json = JSON.parse(result);
    document.getElementById("test_div").innerHTML = json.join("<br/>");
  }
</script>
<div id="test_div">Loading...</div>
