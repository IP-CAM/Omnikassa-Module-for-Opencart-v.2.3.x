<form action="<?php echo $action; ?>" method="GET">
  <div class="buttons">
    <div class="pull-right">
      <input type="hidden" value="<?php echo $token; ?>" name="token" />
      <input type="submit" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
    </div>
  </div>
</form>
