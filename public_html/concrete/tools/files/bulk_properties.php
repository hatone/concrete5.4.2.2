<?php 
defined('C5_EXECUTE') or die("Access Denied.");
$u = new User();
$form = Loader::helper('form');
Loader::model("file_attributes");
$previewMode = false;

$fp = FilePermissions::getGlobal();
if (!$fp->canAccessFileManager()) {
	die(t("Access Denied."));
}  

$attribs = FileAttributeKey::getUserAddedList();

$files = array();
$extensions = array();
$file_versions = array();

//load all the requested files
if (is_array($_REQUEST['fID'])) {
	foreach($_REQUEST['fID'] as $fID) {
		$f = File::getByID($fID);
		$fp = new Permissions($f);
		if ($fp->canRead()) {
			$files[] = $f;
			$extensions[] = strtolower($f->getExtension()); 
		}
	}
} else {
	$f = File::getByID($_REQUEST['fID']);
	$fp = new Permissions($f);
	if ($fp->canRead()) {
		$files[] = $f;
		$extensions[] = strtolower($f->getExtension()); 
	}
} 

//the attributes interface needs a file version
$fv = $f->getVersionToModify();

//Default Values - if all the selected files share the same property, then display, otherwise leave it blank
$defaultPropertyVals=array();
foreach($files as $f){
	$fv = $f->getVersionToModify();
	$title=$fv->getTitle();
	if(!strlen($defaultPropertyVals['title']) || $defaultPropertyVals['title']==$title) {
		 $defaultPropertyVals['title']=$title;  $defaultPropertyVals['titleValue']=$title;
		} else {
		$defaultPropertyVals['title']='{CCM:MULTIPLE:VALUES}';  $defaultPropertyVals['titleValue']='';
		}
		
	$description=$fv->getDescription(); 
	if(!strlen($defaultPropertyVals['description']) || $defaultPropertyVals['description']==$description) {
		 $defaultPropertyVals['description']=$description; $defaultPropertyVals['descriptionValue']=$description;
	} else {
		 $defaultPropertyVals['description']='{CCM:MULTIPLE:VALUES}';  $defaultPropertyVals['descriptionValue']='';
	}
	
	$tags=$fv->getTags(); 
	if(!strlen($defaultPropertyVals['tags']) || $defaultPropertyVals['tags']==$tags) {
		 $defaultPropertyVals['tags']=$tags;  $defaultPropertyVals['tagsValue']=$tags;
	} else {
		$defaultPropertyVals['tags']='{CCM:MULTIPLE:VALUES}';  $defaultPropertyVals['tagsValue']='';
	}
	
	foreach($attribs as $ak){
		$akID=$ak->getAttributeKeyID();
		$vo = $fv->getAttributeValueObject($ak);
		$attrVal = '';
		if (is_object($vo)) {
			$attrVal = $vo->getValue('display');
		}
		if(!isset($defaultPropertyVals['ak'.$akID]) || $defaultPropertyVals['ak'.$akID]==$attrVal) {
			 $defaultPropertyVals['ak'.$akID]=$attrVal;
		} else {
			$defaultPropertyVals['ak' . $akID]='{CCM:MULTIPLE:VALUES}';  $defaultPropertyVals['ak' . $akID . 'Value']='';
		}
	}
}

if ($_POST['task'] == 'update_core' && $fp->canWrite() && (!$previewMode)) { 
 
	switch($_POST['attributeField']) {
		case 'fvTitle':
			$text = $_POST['fvTitle'];
			foreach($files as $f){ 
				$fv=$f->getVersionToModify();
				$fv->updateTitle($text); 
			}
			print $text;
			break;
		case 'fvDescription':
			$text = $_POST['fvDescription'];
			foreach($files as $f){
				$fv=$f->getVersionToModify();
				$fv->updateDescription($text);
			}
			print $text;
			break;
		case 'fvTags':
			$text = $_POST['fvTags'];
			foreach($files as $f){
				$fv=$f->getVersionToModify();
				$fv->updateTags($text);
			}
			print $text;
			break;
	} 
	
	exit;
}

if ($_POST['task'] == 'update_extended_attribute' && $fp->canWrite() && (!$previewMode)) {
	$fv = $f->getVersionToModify();
	$fakID = $_REQUEST['fakID'];
	$value = ''; 
	
	$ak = FileAttributeKey::get($fakID);
	foreach($files as $f){
		$fv=$f->getVersionToModify();
		$ak->saveAttributeForm($fv);
	}
	$fv->populateAttributes();
	$val = $fv->getAttributeValueObject($ak);
	print $val->getValue('display');
	
	exit;
} 

if ($_POST['task'] == 'clear_extended_attribute' && $fp->canWrite() && (!$previewMode)) {

	$fv = $f->getVersionToModify();
	$fakID = $_REQUEST['fakID'];
	$value = ''; 
	
	$ak = FileAttributeKey::get($fakID);
	foreach($files as $f){
		$fv=$f->getVersionToModify();
		$fv->clearAttribute($ak);
	}
	$fv->populateAttributes();
	$val = $fv->getAttributeValueObject($ak);

	print '<div class="ccm-attribute-field-none">' . t('None') . '</div>';
	exit;
}


function printCorePropertyRow($title, $field, $value, $formText) {
	global $previewMode, $f, $fp, $files, $form;
	if ($value == '') {
		$text = '<div class="ccm-attribute-field-none">' . t('None') . '</div>';
	} else if ($value == '{CCM:MULTIPLE:VALUES}') { 
		$text = '<div class="ccm-attribute-field-none">' . t('Multiple Values') . '</div>';
	} else { 
		$text = htmlentities( $value, ENT_QUOTES, APP_CHARSET);
	}

	if ($fp->canWrite() && (!$previewMode)) {
	
	$hiddenFIDfields='';
	foreach($files as $f) {
		$hiddenFIDfields.=' '.$form->hidden('fID[]' , $f->getFileID()).' ';
	}
	
	$html = '
	<tr class="ccm-attribute-editable-field">
		<th><a href="javascript:void(0)">' . $title . '</a></th>
		<td width="100%" class="ccm-attribute-editable-field-central"><div class="ccm-attribute-editable-field-text">' . $text . '</div>
		<form method="post" action="' . REL_DIR_FILES_TOOLS_REQUIRED . '/files/bulk_properties">
			<input type="hidden" name="attributeField" value="' . $field . '" /> 
			'.$hiddenFIDfields.'
			<input type="hidden" name="task" value="update_core" />
			<div class="ccm-attribute-editable-field-form ccm-attribute-editable-field-type-text">
			' . $formText . '
			</div>
		</form>
		</td>
		<td class="ccm-attribute-editable-field-save"><a href="javascript:void(0)"><img src="' . ASSETS_URL_IMAGES . '/icons/edit_small.png" width="16" height="16" class="ccm-attribute-editable-field-save-button" /></a>
		<img src="' . ASSETS_URL_IMAGES . '/throbber_white_16.gif" width="16" height="16" class="ccm-attribute-editable-field-loading" />
		</td>
	</tr>';
	
	} else {
		$html = '
		<tr>
			<th>' . $title . '</th>
			<td width="100%" colspan="2">' . $text . '</td>
		</tr>';	
	}
	
	print $html;
}

function printFileAttributeRow($ak, $fv, $value) {
	global $previewMode, $f, $fp, $files, $form; 
	$vo = $fv->getAttributeValueObject($ak);
	
	if ($value == '') {
		$text = '<div class="ccm-attribute-field-none">' . t('None') . '</div>';
	} else if ($value == '{CCM:MULTIPLE:VALUES}') { 
		$text = '<div class="ccm-attribute-field-none">' . t('Multiple Values') . '</div>';
		$vo = '';
	} else { 
		$text = $value;
	}

	if ($ak->isAttributeKeyEditable() && $fp->canWrite() && (!$previewMode)) { 
	$type = $ak->getAttributeType();
	$hiddenFIDfields='';
	foreach($files as $f) {
		$hiddenFIDfields.=' '.$form->hidden('fID[]' , $f->getFileID()).' ';
	}	
	
	$html = '
	<tr class="ccm-attribute-editable-field">
		<th><a href="javascript:void(0)">' . $ak->getAttributeKeyName() . '</a></th>
		<td width="100%" class="ccm-attribute-editable-field-central"><div class="ccm-attribute-editable-field-text">' . $text . '</div>
		<form method="post" action="' . REL_DIR_FILES_TOOLS_REQUIRED . '/files/bulk_properties">
			<input type="hidden" name="fakID" value="' . $ak->getAttributeKeyID() . '" />
			'.$hiddenFIDfields.'
			<input type="hidden" name="task" value="update_extended_attribute" />
			<div class="ccm-attribute-editable-field-form ccm-attribute-editable-field-type-' . strtolower($type->getAttributeTypeHandle()) . '">
			' . $ak->render('form', $vo, true) . '
			</div>
		</form>
		</td>
		<td class="ccm-attribute-editable-field-save"><a href="javascript:void(0)"><img src="' . ASSETS_URL_IMAGES . '/icons/edit_small.png" width="16" height="16" class="ccm-attribute-editable-field-save-button" /></a>
		<a href="javascript:void(0)"><img src="' . ASSETS_URL_IMAGES . '/icons/close.png" width="16" height="16" class="ccm-attribute-editable-field-clear-button" /></a>
		<img src="' . ASSETS_URL_IMAGES . '/throbber_white_16.gif" width="16" height="16" class="ccm-attribute-editable-field-loading" />
		</td>
	</tr>';
	
	} else {

	$html = '
	<tr>
		<th>' . $ak->getAttributeKeyName() . '</th>
		<td width="100%" colspan="2">' . $text . '</td>
	</tr>';	
	}
	print $html;
}

if (!isset($_REQUEST['reload'])) { ?>
	<div id="ccm-file-properties-wrapper">
<?php  } ?>

<script type="text/javascript">
var ccm_activeFileManagerAddCompleteTab = "ccm-file-manager-add-complete-basic";

$(function() {
	$("#ccm-file-manager-add-complete-tabs a").click(function() {
		$("li.ccm-nav-active").removeClass('ccm-nav-active');
		$("#" + ccm_activeFileManagerAddCompleteTab + "-tab").hide();
		ccm_activeFileManagerAddCompleteTab = $(this).attr('id');
		$(this).parent().addClass("ccm-nav-active");
		$("#" + ccm_activeFileManagerAddCompleteTab + "-tab").show();
	});

	$("div.ccm-message").show('highlight');
});
</script>

<style type="text/css">
div.ccm-add-files-complete div.ccm-message {margin-bottom: 0px}
table.ccm-grid input.ccm-input-text, table.ccm-grid textarea {width: 100%}
table.ccm-grid th {width: 70px}

</style>

<?php  if ($_REQUEST['uploaded']) { ?>
	<?php  if (count($_REQUEST['fID']) == 1) { ?>
		<div class="ccm-message"><strong><?php echo t('1 file uploaded successfully.')?></strong></div>
	<?php  } else { ?>
		<div class="ccm-message"><strong><?php echo t('%s files uploaded successfully.', count($_REQUEST['fID']))?></strong></div>
	<?php  } ?>
<?php  } ?>

<ul class="ccm-dialog-tabs" id="ccm-file-manager-add-complete-tabs">
	<li class="ccm-nav-active"><a href="javascript:void(0)" id="ccm-file-manager-add-complete-basic"><?php echo t('Basic Properties')?></a></li>
	<?php  if (count($attribs) > 0) { ?>
		<li><a href="javascript:void(0)" id="ccm-file-manager-add-complete-attributes"><?php echo t('Other Properties')?></a></li>
	<?php  } ?>
	<?php  if ($_REQUEST['uploaded']) { ?>
		<li><a href="javascript:void(0)" id="ccm-file-manager-add-complete-sets"><?php echo t('Sets')?></a></li>
	<?php  } ?>
</ul>

<div id="ccm-file-properties">
<div id="ccm-file-manager-add-complete-basic-tab">
<table border="0" cellspacing="0" cellpadding="0" class="ccm-grid">  
<?php  if (count($files) == 1) { ?>
<tr>
	<th><?php echo t('ID')?></th>
	<td width="100%" colspan="2"><?php echo $fv->getFileID()?> <span style="color: #afafaf">(<?php echo t('Version')?> <?php echo $fv->getFileVersionID()?>)</span></td>
</tr>
<tr>
	<th><?php echo t('Filename')?></th>
	<td width="100%" colspan="2"><?php echo $fv->getFileName()?></td>
</tr>
<tr>
	<th><?php echo t('URL to File')?></th>
	<td width="100%" colspan="2"><?php echo $fv->getRelativePath(true)?></td>
</tr>

<tr>
	<th><?php echo t('Type')?></th>
	<td colspan="2"><?php echo $fv->getType()?></td>
</tr>

<tr>
	<th><?php echo t('Size')?></th>
	<td colspan="2"><?php echo $fv->getSize()?> (<?php echo number_format($fv->getFullSize())?> <?php echo t('bytes')?>)</td>
</tr>
<?php  } ?>

<?php 
printCorePropertyRow(t('Title'), 'fvTitle', $defaultPropertyVals['title'], $form->text('fvTitle', $defaultPropertyVals['titleValue']));
printCorePropertyRow(t('Description'), 'fvDescription', $defaultPropertyVals['description'], $form->textarea('fvDescription', $defaultPropertyVals['descriptionValue']));
printCorePropertyRow(t('Tags'), 'fvTags', $defaultPropertyVals['tags'], $form->textarea('fvTags', $defaultPropertyVals['tagsValue']));
?>

<?php  if (count($files) == 1) { ?>
<tr>
	<th><?php echo t('File Preview')?></th>
	<td colspan="2"><?php echo $fv->getThumbnail(2)?></td>
</tr>
<?php  } ?>
</table>

</div>

<div id="ccm-file-manager-add-complete-attributes-tab" style="display: none">

<table border="0" cellspacing="0" cellpadding="0" class="ccm-grid" width="100%">  
<?php 
foreach($attribs as $at) { 
	printFileAttributeRow($at, $fv, $defaultPropertyVals['ak' . $at->getAttributeKeyID()]);
} ?>
</table>

</div>
</div>

<?php  if ($_REQUEST['uploaded']) { ?>

	<div id="ccm-file-manager-add-complete-sets-tab" style="display: none">	
		<div class="ccm-files-add-to-sets-wrapper"><?php  Loader::element('files/add_to_sets', array('disableForm' => FALSE, 'disableTitle' => true)) ?></div>
	</div>

<?php  } ?>

<script type="text/javascript">
$(function() { 
	ccm_activateEditablePropertiesGrid();  
});
</script>

<?php 
if (!isset($_REQUEST['reload'])) { ?>
</div>
<?php  }
