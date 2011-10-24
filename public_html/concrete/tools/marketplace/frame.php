<?php   defined('C5_EXECUTE') or die("Access Denied.");

$tp = new TaskPermission();
if ($tp->canInstallPackages()) { 
	Loader::library('marketplace');
	$mi = Marketplace::getInstance();
	if ($_REQUEST['complete']) { 
	
		Config::save('MARKETPLACE_SITE_TOKEN', $_POST['csToken']);
		Config::save('MARKETPLACE_SITE_URL_TOKEN', $_POST['csURLToken']);
	
		?>
		<script type="text/javascript">
			<?php  if ($_REQUEST['mpID']) { ?>
				parent.ccm_getMarketplaceItem({mpID: '<?php echo $_REQUEST['mpID']?>', closeTop: true});
			<?php  } ?>
		</script>
	<?php  } else {
		$completeURL = BASE_URL . REL_DIR_FILES_TOOLS_REQUIRED . '/marketplace/frame?complete=1&mpID=' . $_REQUEST['mpID'];
		print $mi->getMarketplaceFrame('100%', '100%', $completeURL);
	}
} else {
	print t('You do not have permission to connect to the marketplace.');
}