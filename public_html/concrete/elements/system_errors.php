<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

if (isset($error) && $error != '') {
	if ($error instanceof Exception) {
		$_error[] = $error->getMessage();
	} else if ($error instanceof ValidationErrorHelper) { 
		$_error = $error->getList();
	} else if (is_array($error)) {
		$_error = $error;
	} else if (is_string($error)) {
		$_error[] = $error;
	}
	?>
	
	<ul class="ccm-error">
	<?php  foreach($_error as $e): ?>
		<li><?php  echo $e?></li>
	<?php  endforeach; ?>
	</ul>

<?php  } ?>
