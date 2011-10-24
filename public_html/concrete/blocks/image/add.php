<?php   

defined('C5_EXECUTE') or die("Access Denied.");
$includeAssetLibrary = true;
$assetLibraryPassThru = array(
	'type' => 'image'
);
$al = Loader::helper('concrete/asset_library');
?>
<div class="ccm-block-field-group">
<h2><?php echo t('Image')?></h2>
<?php echo $al->image('ccm-b-image', 'fID', t('Choose Image'), $bf);?>
</div>
<div class="ccm-block-field-group">
<h2><?php echo t('Image On-State')?> (<?php echo t('Optional')?>)</h2>
<?php echo $al->image('ccm-b-image-onstate', 'fOnstateID', t('Choose Image On-State'), $bfo);?>
</div>

<div class="ccm-block-field-group">
<h2><?php echo t('Image Links to URL')?></h2>
<?php echo  $form->text('externalLink', array('style' => 'width: 250px')); ?>
</div>

<div class="ccm-block-field-group">
<h2><?php echo t('Alt Text/Caption')?></h2>
<?php echo  $form->text('altText', array('style' => 'width: 250px')); ?>
</div>

<div class="ccm-block-field-group">
<h2><?php echo t('Maximum Dimensions')?></h2>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td><?php echo t('Width')?>&nbsp;</td>
<td><?php echo  $form->text('maxWidth', array('style' => 'width: 60px')); ?></td>
<td><?php echo t('Height')?>&nbsp;</td>
<td><?php echo  $form->text('maxHeight', array('style' => 'width: 60px')); ?></td>
</tr>
</table>

</div>