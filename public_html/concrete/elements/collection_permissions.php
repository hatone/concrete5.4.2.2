<?php 
defined('C5_EXECUTE') or die("Access Denied.");
Loader::model('collection_types');
$dh = Loader::helper('date');
$dt = Loader::helper('form/date_time');

if ($cp->canAdminPage()) {
	// if it's composer mode and we have a target, then we hack the permissions collection id
	if (isset($isComposer) && $isComposer) {
		$cd = ComposerPage::getByID($c->getCollectionID());
		if ($cd->isComposerDraft()) {
			if ($cd->getComposerDraftPublishParentID() > 0) {
				if ($cd->getCollectionInheritance() == 'PARENT') {
					$c->cParentID = $cd->getComposerDraftPublishParentID();
					$cpID = $c->getParentPermissionsCollectionID();
					$c->cInheritPermissionsFromCID = $cpID;
				}
			}
		}
	}

	$gl = new GroupList($c);
	$gArray = $gl->getGroupList();
	$ul = new UserInfoList($c);
	$ulArray = $ul->getUserInfoList();
	$ctArray = CollectionType::getList($c);
}
$saveMsg = t('Save permissions first.');
?>
<div class="ccm-pane-controls">

<form method="post" id="ccmPermissionsForm" name="ccmPermissionsForm" action="<?php echo $c->getCollectionAction()?>">
<input type="hidden" name="rel" value="<?php echo $_REQUEST['rel']?>" />

	<script type="text/javascript">
		var activeSlate = "permissions";	
		function togglePermissionsGrid(value, doReset) {
			$("td." + activeSlate).hide();
			$("td." + value).show();
			$("th." + activeSlate).hide();
			$("th." + value).show();
			
			activeSlate = value;
			if (doReset) {
				$("#toggleGrid").get(0).selectedIndex = 0;
			}
		}
		
		function ccm_triggerSelectUser(uID, uName) {
			togglePermissionsGrid('permissions', true);
			rowValue = "uID:" + uID;
			existingRow = document.getElementById("_row:" + rowValue);		  
			if (!existingRow) {
				tbl = document.getElementById("ccmPermissionsTablePage");

				row = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?                            
				row.id = "_row:" + rowValue;
				
				ccm_setupGridStriping('ccmPermissionsTablePage');
				
				cells = new Array();
				target = 10;
				for (i = 0; i < target; i++) {
					cells[i] = row.insertCell(i);
					if (i < 7 && i > 0) {
						cells[i].className = "permissions";
					} else if (i == 7) {
						cells[i].className = "subpage";
					} else {
						cells[i].className = "datetime";
					}					
				}
				
				cells[0].className = "actor";
				cells[0].innerHTML = '<a href="javascript:removePermissionRow(\'_row:' + rowValue + '\')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12"></a>' + uName;
				cells[1].innerHTML = '<input type="checkbox" name="collectionRead[]" value="' + rowValue + '">';
				cells[2].innerHTML = '<input type="checkbox" name="collectionReadVersions[]" value="' + rowValue + '">';
				cells[3].innerHTML = '<input type="checkbox" name="collectionWrite[]" value="' + rowValue + '">';
				cells[4].innerHTML = '<input type="checkbox" name="collectionApprove[]" value="' + rowValue + '">';
				cells[5].innerHTML = '<input type="checkbox" name="collectionDelete[]" value="' + rowValue + '">';
				cells[6].innerHTML = '<input type="checkbox" name="collectionAdmin[]" value="' + rowValue +'">';
				<?php  
				foreach ($ctArray as $ct) { ?>
					cells[7].innerHTML += '<div style="white-space: nowrap; width: auto; float: left; margin-right: 5px; min-width: 90px"><input type="checkbox" name="collectionAddSubCollection[<?php echo $ct->getCollectionTypeID()?>][]" value="' + rowValue + '">&nbsp;<?php echo $ct->getCollectionTypeName()?></div>';
				<?php  } ?>
				cells[7].innerHTML += '<div class="ccm-spacer">&nbsp;</div>';
				cells[8].innerHTML = '<div style="text-align: center; color: #aaa"><?php echo $saveMsg?></div>';
				cells[9].innerHTML = '<div style="text-align: center; color: #aaa"><?php echo $saveMsg?></div>';
			}
		}
		
		function ccm_triggerSelectGroup(gID, gName) {
			togglePermissionsGrid('permissions', true);
			// we add a row for the selected group
			rowValue = 'gID:' + gID;
			rowText = gName;
			existingRow = document.getElementById("_row:" + rowValue);
			if (rowValue && (existingRow == null)) {
				tbl = document.getElementById("ccmPermissionsTablePage");	      

				row = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?                            
				row.id = "_row:" + rowValue;
				
				ccm_setupGridStriping('ccmPermissionsTablePage');
				
				cells = new Array();
				
				target = 10;
				for (i = 0; i < target; i++) {
					cells[i] = row.insertCell(i);
					if (i < 7 && i > 0) {
						cells[i].className = "permissions";
					} else if (i == 7) {
						cells[i].className = "subpage";
					} else {
						cells[i].className = "datetime";
					}					
				}
				
				cells[0].className = "actor";
				cells[0].innerHTML = '<a href="javascript:removePermissionRow(\'_row:' + rowValue + '\',\'' + rowValue + '\',\'' + rowText + '\')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12"></a>' + rowText;
				cells[1].innerHTML = '<input type="checkbox" name="collectionRead[]" value="' + rowValue + '">';
				cells[2].innerHTML = '<input type="checkbox" name="collectionReadVersions[]" value="' + rowValue + '">';
				cells[3].innerHTML = '<input type="checkbox" name="collectionWrite[]" value="' + rowValue + '">';
				cells[4].innerHTML = '<input type="checkbox" name="collectionApprove[]" value="' + rowValue + '">';
				cells[5].innerHTML = '<input type="checkbox" name="collectionDelete[]" value="' + rowValue + '">';
				cells[6].innerHTML = '<input type="checkbox" name="collectionAdmin[]" value="' + rowValue +'">';
				<?php  
				foreach ($ctArray as $ct) { ?>
					cells[7].innerHTML += '<div style="white-space: nowrap; width: auto; float: left; margin-right: 5px; min-width: 90px"><input type="checkbox" name="collectionAddSubCollection[<?php echo $ct->getCollectionTypeID()?>][]" value="' + rowValue + '">&nbsp;<?php echo $ct->getCollectionTypeName()?></div>';
				<?php  } ?>
				cells[7].innerHTML += '<div class="ccm-spacer">&nbsp;</div>';
				cells[8].innerHTML = '<div style="text-align: center; color: #aaa"><?php echo $saveMsg?></div>';
				cells[9].innerHTML = '<div style="text-align: center; color: #aaa"><?php echo $saveMsg?></div>';
				
			}            
		}
		
		function removePermissionRow(rowID, origValue, origText) {
		  oRow = document.getElementById(rowID);
		  oRowInputs = oRow.getElementsByTagName("INPUT");
	    	  for (i = 0; i < oRowInputs.length; i++) {
	    	       oRowInputs[i].checked = false;
	    	  }
	    	  oRow.id = null;
	    	  oRow.style.display = "none";

			  ccm_setupGridStriping('ccmPermissionsTablePage');
			}
			
	</script>
	<?php  if ($cp->canAdminPage()) { ?>
		<style type="text/css">
			.datetime {display: none}
			.subpage {display: none}
			#ccmPermissionsTablePage .ccm-input-time-wrapper {display: block; margin-left: 14px}
			td.permissions {text-align: center}
			
			#ccmPermissionsTablePage .ccm-input-date-wrapper input.ccm-input-date {width: 120px;}
			
		</style>
		
		<h1 style="margin-bottom: 0px">Page Permissions</h1>

		<div class="ccm-buttons" style="width: 140px; float: right" id="ccm-page-permissions-select-user-group"> 
		<a href="<?php echo REL_DIR_FILES_TOOLS_REQUIRED?>/user_group_selector.php?cID=<?php echo $_REQUEST['cID']?>" dialog-modal="false" dialog-width="90%" dialog-title="<?php echo t('Add User/Group')?>"  dialog-height="70%" class="dialog-launch ccm-button-right"><span><em class="ccm-button-add"><?php echo t('Add User/Group')?></em></span></a>
		</div>		

		<div style="float: left; width: 450px; padding-top: 15px">
		  <strong><?php echo t('Set')?></strong>&nbsp;
		   <select id="ccmToggleInheritance" style="width: 130px" name="cInheritPermissionsFrom">
			<?php  if ($c->getCollectionID() > 1) { ?><option value="PARENT" <?php  if ($c->getCollectionInheritance() == "PARENT") { ?> selected<?php  } ?>><?php echo t('By Area of Site (Hierarchy)')?></option><?php  } ?>
			<?php  if ($c->getMasterCollectionID() > 1) { ?><option value="TEMPLATE"  <?php  if ($c->getCollectionInheritance() == "TEMPLATE") { ?> selected<?php  } ?>><?php echo t('By Page Type Defaults (in Dashboard)')?></option><?php  } ?>
			<option value="OVERRIDE" <?php  if ($c->getCollectionInheritance() == "OVERRIDE") { ?> selected<?php  } ?>><?php echo t('Manually')?></option>
		  </select>
		  
		  &nbsp;&nbsp;&nbsp;

		  <strong><?php echo t('Currently Viewing')?></strong>&nbsp;
		  <select id="toggleGrid" style="width: 130px" onchange="togglePermissionsGrid(this.value)">
			<option value="permissions" selected><?php echo t('Page Permissions')?></option>
			<option value="subpage"><?php echo t('Sub-Page Permissions')?></option>
			<option value="datetime"><?php echo t('Timed Release Settings')?></option>
		  </select>
		</div>
		
		<div class="ccm-spacer" style="margin-bottom: 8px">&nbsp;</div>
		
		
		 <?php 
		  $cpc = $c->getPermissionsCollectionObject();
		  $isManual = ($c->getCollectionInheritance() == "OVERRIDE");
	  		if ($c->getCollectionInheritance() == "PARENT") { ?>
			<strong><?php echo t('This page inherits its permissions from:');?> <a target="_blank" href="<?php echo DIR_REL?>/<?php echo DISPATCHER_FILENAME?>?cID=<?php echo $cpc->getCollectionID()?>"><?php echo $cpc->getCollectionName()?></a></strong><br/><br/>
			<?php  } ?>		
				
            <table id="ccmPermissionsTablePage" width="100%" class="ccm-grid" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <th><div style="width: 200px">&nbsp;</div></th>
              <th class="permissions"><?php echo t('Read')?></th>
              <th class="permissions"><?php echo t('Versions')?></th>
              <th class="permissions"><?php echo t('Write')?></th>
              <th class="permissions"><?php echo t('Approve')?></th>
              <th class="permissions"><?php echo t('Delete')?></th>
              <th class="permissions"><?php echo t('Admin')?></th>
              <th class="subpage"><?php echo t('User/Group May Add the Following Pages')?>:</th>
              <th class="datetime"><?php echo t('Start Date/Time')?></th>
              <th class="datetime"><?php echo t('End Date/Time')?></th>              	
            </tr>
            <?php  
            $rowNum = 1;
            foreach ($gArray as $g) { 
                $displayRow = false;
                
                $display = (($g->getGroupID() == GUEST_GROUP_ID || $g->getGroupID() == REGISTERED_GROUP_ID) || $g->canRead() || $g->canWrite() || $g->canAddSubContent() || $g->canApproveCollection() || $g->canDeleteCollection() || ($c->isMasterCollection() && $g->canAddSubCollection())) 
                ? true : false;
                       
                if ($display) { ?>
                    <tr id="_row:gID:<?php echo $g->getGroupID()?>">
                        <td class="actor" style="width: 1%; white-space: nowrap">
                            <?php  if ($g->getGroupID() != GUEST_GROUP_ID && $g->getGroupID() != REGISTERED_GROUP_ID) { ?>    
                                <a href="javascript:removePermissionRow('_row:gID:<?php echo $g->getGroupID()?>','gID:<?php echo $g->getGroupID()?>', '<?php echo $g->getGroupName()?>')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12"></a>
							<?php  } ?>
							<?php echo $g->getGroupName()?>
						</td>
						<td class="permissions"><input type="checkbox" name="collectionRead[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canRead()) { ?>checked<?php  } ?>></td>
                        <td class="permissions"><input type="checkbox" name="collectionReadVersions[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canReadVersions()) { ?>checked<?php  } ?>></td>

                        <td class="permissions"><input type="checkbox" name="collectionWrite[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canWrite()) { ?>checked<?php  } ?>></td>
                        <td class="permissions"><input type="checkbox" name="collectionApprove[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canApproveCollection()) { ?>checked<?php  } ?>></td>
                        <td class="permissions"><input type="checkbox" name="collectionDelete[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canDeleteCollection()) { ?>checked<?php  } ?>></td>
                   		<td class="permissions"><input type="checkbox" name="collectionAdmin[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canAdminCollection()) { ?> checked<?php  } ?>></td>
                   		
                   		<td class="subpage"><?php  foreach ($ctArray as $ct) { ?><div style="white-space: nowrap; width: auto; float: left; margin-right: 5px; min-width: 90px"><input type="checkbox" name="collectionAddSubCollection[<?php echo $ct->getCollectionTypeID()?>][]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($ct->canAddSubCollection($g)) { ?> checked<?php  } ?>>&nbsp;<?php echo $ct->getCollectionTypeName()?></div><?php  } ?><div class="ccm-spacer">&nbsp;</div></td>
						<td class="datetime"><?php 	print $dt->datetime('cgStartDate_gID:' . $g->getGroupID(), $g->getGroupStartDate('user'), true); ?>
						<td class="datetime"><?php 	print $dt->datetime('cgEndDate_gID:' . $g->getGroupID(), $g->getGroupEndDate('user'), true); ?>
				
                  
                  </tr>
                <?php  
                    $rowNum++;
                    } ?>
            <?php   }
                        
            foreach ($ulArray as $ui) { 
				?>
               <tr id="_row:uID:<?php echo $ui->getUserID()?>">
                    <td class="actor">
                        <a href="javascript:removePermissionRow('_row:uID:<?php echo $ui->getUserID()?>')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12"></a>
                        <?php echo $ui->getUserName()?>                            
                    </td>
                    <td class="permissions"><input type="checkbox" name="collectionRead[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canRead()) { ?>checked<?php  } ?>></td>
                    <td class="permissions"><input type="checkbox" name="collectionReadVersions[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canReadVersions()) { ?>checked<?php  } ?>></td>
                    <td class="permissions"><input type="checkbox" name="collectionWrite[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canWrite()) { ?>checked<?php  } ?>></td>
                    <td class="permissions"><input type="checkbox" name="collectionApprove[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canApproveCollection()) { ?>checked<?php  } ?>></td>
                    <td class="permissions"><input type="checkbox" name="collectionDelete[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canDeleteCollection()) { ?>checked<?php  } ?>></td>
              		<td class="permissions"><input type="checkbox" name="collectionAdmin[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canAdminCollection()) { ?> checked<?php  } ?>></td>
              		<td class="subpage">
					<?php  foreach ($ctArray as $ct) { ?>
						<div style="white-space: nowrap; width: auto; float: left; margin-right: 5px; min-width: 90px"><input type="checkbox" name="collectionAddSubCollection[<?php echo $ct->getCollectionTypeID()?>][]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ct->canAddSubCollection($ui)) { ?> checked<?php  } ?>>&nbsp;<?php echo $ct->getCollectionTypeName()?></div><?php  } ?>
					<div class="ccm-spacer">&nbsp;</div>
					</td>
              		<td class="datetime"><input type="text" id="cgStartDate_uID:<?php echo $ui->getUserID()?>" name="cgStartDate_uID:<?php echo $ui->getUserID()?>" value="<?php echo $ui->getUserStartDate()?>" style="width: 130px">&nbsp;<input type="button" name="" value="date" onclick="popUpCalendar(this, document.getElementById('cgStartDate_uID:<?php echo $ui->getUserID()?>'), 'yyyy-mm-dd')"></td>
					<td class="datetime"><input type="text" id="cgEndDate_uID:<?php echo $ui->getUserID()?>" name="cgEndDate_uID:<?php echo $ui->getUserID()?>" value="<?php echo $ui->getUserEndDate()?>" style="width: 130px">&nbsp;<input type="button" name="" value="date" onclick="popUpCalendar(this, document.getElementById('cgEndDate_uID:<?php echo $ui->getUserID()?>'), 'yyyy-mm-dd')"></td>

                </tr>
            <?php  
                $rowNum++;
                } ?>
            </table>		
            <br/>
            <?php  if (!$c->isMasterCollection()) { ?>
				<b><?php echo t('Sub-pages added beneath this page')?></b>: 
				<select id="templatePermissionsSelect" name="cOverrideTemplatePermissions">
					<option value="0"<?php  if (!$c->overrideTemplatePermissions()) { ?>selected<?php  } ?>><?php echo t('Inherit page type default permissions.')?></option>
					<option value="1"<?php  if ($c->overrideTemplatePermissions()) { ?>selected<?php  } ?>><?php echo t('Inherit the permissions of this page.')?></option>
				</select>
				<br><br>
				<?php  } ?>
				
			<div class="ccm-buttons">
<!--				<a href="javascript:void(0)" onclick="ccm_hidePane()" class="ccm-button-left cancel"><span><em class="ccm-button-close">Cancel</em></span></a>//-->
				<a href="javascript:void(0)" onclick="$('form[name=ccmPermissionsForm]').submit()" class="ccm-button-right accept"><span><?php echo t('Save')?></span></a>
			</div>	
			<input type="hidden" name="update_permissions" value="1" class="accept">
			<input type="hidden" name="processCollection" value="1">

<script type="text/javascript">
ccm_deactivatePermissionsTable = function() {
	$("#ccmPermissionsTablePage input, #ccmPermissionsTablePage select").each(function(i) {
		$(this).get(0).disabled = true;
	});
	$("#ccm-page-permissions-select-user-group").hide();
}

ccm_activatePermissionsTable = function() {
	$("#ccmPermissionsTablePage input, #ccmPermissionsTablePage select").each(function(i) {
		$(this).get(0).disabled = false;
	});
	$("input.ccm-activate-date-time").each(function() {
		var thisID = $(this).attr('ccm-date-time-id');
		if (!$(this).get(0).checked) {
			$("#" + thisID + "_dw input").each(function() {
				$(this).get(0).disabled = true;
			});
			$("#" + thisID + "_tw select").each(function() {
				$(this).get(0).disabled = true;
			});		}
	});
	$("#ccm-page-permissions-select-user-group").show();
}
$(function() {	
	<?php  if (!$isManual) { ?>
		ccm_deactivatePermissionsTable();
	<?php  } ?>
	$("#ccmPermissionsForm").ajaxForm({
		type: 'POST',
		iframe: true,
		beforeSubmit: function() {
			jQuery.fn.dialog.showLoader();
		},
		success: function(r) {
			var r = eval('(' + r + ')');
			<?php  if (isset($isComposer) && $isComposer) { ?>
				jQuery.fn.dialog.hideLoader();						
				jQuery.fn.dialog.closeTop();
			<?php  } else { ?>
				if (r != null && r.rel == 'SITEMAP') {
					jQuery.fn.dialog.hideLoader();
					jQuery.fn.dialog.closeTop();
					ccmSitemapHighlightPageLabel(r.cID);
				} else {
					ccm_hidePane(function() {
						jQuery.fn.dialog.hideLoader();						
					});
				}
			<?php  } ?>
			ccmAlert.hud(ccmi18n_sitemap.setPagePermissionsMsg, 2000, 'success', ccmi18n_sitemap.setPagePermissions);
		}
	});
	$("#ccmToggleInheritance").change(function() {
		if ($(this).val() == 'OVERRIDE') {
			ccm_activatePermissionsTable();
		} else {
			ccm_deactivatePermissionsTable();
		}
	});
	ccm_setupGridStriping('ccmPermissionsTablePage'); 
});
</script>

	<?php  } ?>
</form>
<div class="ccm-spacer">&nbsp;</div>
</div>