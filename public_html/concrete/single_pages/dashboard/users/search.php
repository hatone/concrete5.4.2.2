<?php 

$attribs = UserAttributeKey::getList(true);
$u = new User();
$uh = Loader::helper('concrete/user');
$txt = Loader::helper('text');
$vals = Loader::helper('validation/strings');
$valt = Loader::helper('validation/token');
$valc = Loader::helper('concrete/validation');
$dtt = Loader::helper('form/date_time');
$dh = Loader::helper('date');
$form = Loader::helper('form');
$ih = Loader::helper('concrete/interface');
$av = Loader::helper('concrete/avatar'); 

if ($_REQUEST['user_created'] == 1) {
	$message = t('User created successfully. ');
}

function printAttributeRow($ak, $uo) {
	
	$vo = $uo->getAttributeValueObject($ak);
	$value = '';
	if (is_object($vo)) {
		$value = $vo->getValue('displaySanitized', 'display');
	}
	
	if ($value == '') {
		$text = '<div class="ccm-attribute-field-none">' . t('None') . '</div>';
	} else {
		$text = $value;
	}
	if ($ak->isAttributeKeyEditable()) { 
	$type = $ak->getAttributeType();
	
	$html = '
	<tr class="ccm-attribute-editable-field">
		<td style="white-space: nowrap; padding-right: 20px"><strong><a href="javascript:void(0)">' . $ak->getAttributeKeyDisplayHandle() . '</a></strong></td>
		<td width="100%" class="ccm-attribute-editable-field-central"><div class="ccm-attribute-editable-field-text">' . $text . '</div>
		<form method="post" action="' . View::url('/dashboard/users/search', 'edit_attribute') . '">
		<input type="hidden" name="uakID" value="' . $ak->getAttributeKeyID() . '" />
		<input type="hidden" name="uID" value="' . $uo->getUserID() . '" />
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
		<th>' . $ak->getAttributeKeyDisplayHandle() . '</th>
		<td width="100%" colspan="2">' . $text . '</td>
	</tr>';	
	}
	print $html;
}


if (intval($_GET['uID'])) {
	
	$uo = UserInfo::getByID(intval($_GET['uID']));
	if (is_object($uo)) {
		$uID = intval($_REQUEST['uID']);
		
		if (isset($_GET['task'])) {
			if ($uo->getUserID() == USER_SUPER_ID && (!$u->isSuperUser())) {
				throw new Exception(t('Only the super user may edit this account.'));
			}
		}
		
		if ($_GET['task'] == 'activate') {
			if( !$valt->validate("user_activate") ){
				throw new Exception('Invalid token.  Unable to activate user.');
			}else{		
				$uo->activate();
				$uo = UserInfo::getByID(intval($_GET['uID']));
				$message = t("User activated.");
			}
		}

		if ($_GET['task'] == 'validate_email') {
			$uo->markValidated();
			$uo = UserInfo::getByID(intval($_GET['uID']));
			$message = t("Email marked as valid.");
		}
		
		
		if ($_GET['task'] == 'remove-avatar') {
			$av->removeAvatar($uo->getUserID());
			$this->controller->redirect('/dashboard/users/search?uID=' . intval($_GET['uID']) . '&task=edit');

		}
		
		if ($_GET['task'] == 'deactivate') {
			if( !$valt->validate("user_deactivate") ){
				throw new Exception('Invalid token.  Unable to deactivate user.');
			}else{
				$uo->deactivate();
				$uo = UserInfo::getByID(intval($_GET['uID']));
				$message = t("User deactivated.");
			}
		}
		
		if ($_POST['edit']) { 
			
			$username = trim($_POST['uName']);
			$username = preg_replace("/\s+/", " ", $username);
			$_POST['uName'] = $username;
			
			$password = $_POST['uPassword'];
			$passwordConfirm = $_POST['uPasswordConfirm'];
			
			if ($password) {
				if ((strlen($password) < USER_PASSWORD_MINIMUM) || (strlen($password) > USER_PASSWORD_MAXIMUM)) {
					$error[] = t('A password must be between %s and %s characters',USER_PASSWORD_MINIMUM,USER_PASSWORD_MAXIMUM);
				}
			}
			
			if (!$vals->email($_POST['uEmail'])) {
				$error[] = t('Invalid email address provided.');
			} else if (!$valc->isUniqueEmail($_POST['uEmail']) && $uo->getUserEmail() != $_POST['uEmail']) {
				$error[] = t("The email address '%s' is already in use. Please choose another.",$_POST['uEmail']);
			}
			
			if (USER_REGISTRATION_WITH_EMAIL_ADDRESS == false) {
				if (strlen($username) < USER_USERNAME_MINIMUM) {
					$error[] = t('A username must be at least %s characters long.',USER_USERNAME_MINIMUM);
				}
	
				if (strlen($username) > USER_USERNAME_MAXIMUM) {
					$error[] = t('A username cannot be more than %s characters long.',USER_USERNAME_MAXIMUM);
				}

				/*
				if (strlen($username) >= USER_USERNAME_MINIMUM && !$vals->alphanum($username,USER_USERNAME_ALLOW_SPACES)) {
					if(USER_USERNAME_ALLOW_SPACES) {
						$e->add(t('A username may only contain letters, numbers and spaces.'));
					} else {
						$e->add(t('A username may only contain letters or numbers.'));
					}
					
				}
				*/
				
				if (strlen($username) >= USER_USERNAME_MINIMUM && !$valc->username($username)) {
					if(USER_USERNAME_ALLOW_SPACES) {
						$error[] = t('A username may only contain letters, numbers and spaces.');
					} else {
						$error[] = t('A username may only contain letters or numbers.');
					}
				}
				if (!$valc->isUniqueUsername($username) && $uo->getUserName() != $username) {
					$error[] = t("The username '%s' already exists. Please choose another",$username);
				}		
			}
			
			if (strlen($password) >= USER_PASSWORD_MINIMUM && !$valc->password($password)) {
				$error[] = t('A password may not contain ", \', >, <, or any spaces.');
			}
			
			if ($password) {
				if ($password != $passwordConfirm) {
					$error[] = t('The two passwords provided do not match.');
				}
			}
			
			if (!$valt->validate('update_account_' . intval($_GET['uID']) )) {
				$error[] = $valt->getErrorMessage();
			}
		
			if (!$error) {
				// do the registration
				$process = $uo->update($_POST);
				
				//$db = Loader::db();
				if ($process) {
					if ( is_uploaded_file($_FILES['uAvatar']['tmp_name']) ) {
						$uHasAvatar = $av->updateUserAvatar($_FILES['uAvatar']['tmp_name'], $uo->getUserID());
					}
					
					$uo->updateGroups($_POST['gID']);

					$message = t("User updated successfully. ");
					if ($password) {
						$message .= t("Password changed.");
					}
					$editComplete = true;
					// reload user object
					$uo = UserInfo::getByID(intval($_GET['uID']));
				} else {
					$db = Loader::db();
					$error[] = $db->ErrorMsg();
				}
			}		
		}	
	}
}


if (is_object($uo)) { 
	$gl = new GroupList($uo, true);
	if ($_GET['task'] == 'edit' || $_POST['edit'] && !$editComplete) { ?>

		<div class="wrapper">
		<div class="actions">
		<span class="required">*</span> - <?php echo t('required field')?>
		</div>
		
		<?php 
		$uName = ($_POST) ? $_POST['uName'] : $uo->getUserName();
		$uEmail = ($_POST) ? $_POST['uEmail'] : $uo->getUserEmail();
		?>
		
	<script>	
	function editAttrVal(attId,cancel){
		if(!cancel){
			$('#attUnknownWrap'+attId).css('display','none');
			$('#attEditWrap'+attId).css('display','block');
			$('#attValChanged'+attId).val(attId);	
		}else{
			$('#attUnknownWrap'+attId).css('display','block');
			$('#attEditWrap'+attId).css('display','none');
			$('#attValChanged'+attId).val(0);	
		}
	}
	</script>
		
		
	<h1><span><?php echo t('Edit Account')?></span></h1>
	
	<div class="ccm-dashboard-inner">

		<form method="post" enctype="multipart/form-data" id="ccm-user-form" action="<?php echo $this->url('/dashboard/users/search?uID=' . intval($_GET['uID']) )?>">
		<?php echo $valt->output('update_account_' . intval($_GET['uID']) )?>
		<input type="hidden" name="_disableLogin" value="1">
	
		<div style="margin:0px; padding:0px; width:100%; height:auto" >
		<table class="entry-form" border="0" cellspacing="1" cellpadding="0">
		<tr>
			<td colspan="3" class="header"><?php echo t('Core Information')?></td>
		</tr>
		<tr>
			<td class="subheader"><?php echo t('Username')?> <span class="required">*</span></td>
			<td class="subheader"><?php echo t('Email Address')?> <span class="required">*</span></td>
			<td class="subheader"><?php echo t('User Avatar')?></td>
		</tr>	
		<tr>
			<td><input type="text" name="uName" autocomplete="off" value="<?php echo $uName?>" style="width: 94%"></td>
			<td><input type="text" name="uEmail" autocomplete="off" value="<?php echo $uEmail?>" style="width: 94%"></td>
			<td><input type="file" name="uAvatar" style="width: 94%" /> <input type="hidden" name="uHasAvatar" value="<?php echo $uo->hasAvatar()?>" />
			
			<?php  if ($uo->hasAvatar()) { ?>
			<input type="button" onclick="location.href='<?php echo $this->url('/dashboard/users/search?uID=' . intval($uID) . '&task=remove-avatar')?>'" value="<?php echo t('Remove Avatar')?>" />
			<?php  } ?>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="header"><?php echo t('Change Password')?></td>
		</tr>
		<tr>
			<td class="subheader"><?php echo t('Password')?></td>
			<td class="subheader" colspan="2"><?php echo t('Password (Confirm)')?></td>
		</tr>
        <tr>
			<td><input type="password" name="uPassword" autocomplete="off" value="" style="width: 94%"></td>
			<td><input type="password" name="uPasswordConfirm" autocomplete="off" value="" style="width: 94%"></td>
			<td><?php echo t('(Leave these fields blank to keep the same password)')?></td>
		</tr>
		<?php 
		$languages = Localization::getAvailableInterfaceLanguages();
		if (count($languages) > 0) { ?>
	
		<tr>
			<td class="subheader" colspan="3"><?php echo t('Default Language')?></td>
		</tr>	
		<tr>
			<Td colspan="3">
			<?php 
				array_unshift($languages, 'en_US');
				$locales = array();
				Loader::library('3rdparty/Zend/Locale');
				Loader::library('3rdparty/Zend/Locale/Data');
				$locales[''] = t('** Default');
				Zend_Locale_Data::setCache(Cache::getLibrary());
				foreach($languages as $lang) {
					$loc = new Zend_Locale($lang);
					$locales[$lang] = Zend_Locale::getTranslation($loc->getLanguage(), 'language', ACTIVE_LOCALE);
				}
				$ux = $uo->getUserObject();
				print $form->select('uDefaultLanguage', $locales, $ux->getUserDefaultLanguage());
			?>
			</td>
		</tr>	
		<?php  } ?>

		<?php  if(ENABLE_USER_TIMEZONES) { ?>
        <tr>
        	<td class="subheader" colspan="3"><?php echo t('Time Zone')?></td>
        </tr>
        <tr>
			<td colspan="3">
            	<?php  
				echo $form->select('uTimezone', 
						$dh->getTimezones(), 
						($uo->getUserTimezone()?$uo->getUserTimezone():date_default_timezone_get())
					); ?>
            </td>
		</tr>
        <?php  } ?>
        <tr>
			<td colspan="3" class="header">
				<a id="groupSelector" href="<?php echo REL_DIR_FILES_TOOLS_REQUIRED?>/user_group_selector.php?mode=groups" dialog-title="<?php echo t('Add Groups')?>" dialog-modal="false" style="float: right"><?php echo t('Add Group')?></a>
				<?php echo t('Groups')?>
			</td>
		</tr>
		<?php  $gArray = $gl->getGroupList(); ?>
		<tr>
			<td colspan="3">
			<?php  foreach ($gArray as $g) { ?>
				<input type="checkbox" name="gID[]" value="<?php echo $g->getGroupID()?>" style="vertical-align: middle" <?php  
					if (is_array($_POST['gID'])) {
						if (in_array($g->getGroupID(), $_POST['gID'])) {
							echo(' checked ');
						}
					} else {
						if ($g->inGroup()) {
							echo(' checked ');
						}
					}
				?> /> <?php echo $g->getGroupName()?><br>
			<?php  } ?>
			
			<div id="ccm-additional-groups"></div>
			
			</td>
		</tr>
		</table>
        
        <input type="hidden" name="edit" value="1" />

		<div class="ccm-buttons">
		
		<?php echo Loader::helper('concrete/interface')->button(t('Back'), $this->url('/dashboard/users/search?uID=' . intval($_GET['uID'])), 'left')?>
		<?php echo Loader::helper('concrete/interface')->submit(t('Update User'))?>

		</div>	
		</form>

		<div class="ccm-spacer">&nbsp;</div>
		
		<br/>
		
		<table class="entry-form" border="0" cellspacing="1" cellpadding="0">
		<tr>
			<td colspan="3" class="header"><?php echo t('Other Information - Click Field Name to Edit')?></td>
		</tr>
		<?php 
	
		$attribs = UserAttributeKey::getEditableList();
		foreach($attribs as $ak) { 
			printAttributeRow($ak, $uo);
		} ?>
		</table>
		

		</div>
		
		<div class="ccm-spacer">&nbsp;</div>
		
	</div>
	
	<?php  } else { ?>

	<h1><span><?php echo t('View User')?></span></h1>
	
	<div class="ccm-dashboard-inner">
		<div class="actions" >			
		
			<?php  if ($uo->getUserID() != USER_SUPER_ID || $u->isSuperUser()) { ?>
	
				<?php  print $ih->button(t('Edit User'), $this->url('/dashboard/users/search?uID=' . intval($uID) ) . '&task=edit', 'left');?>
	
				<?php  if (USER_VALIDATE_EMAIL == true) { ?>
					<?php  if ($uo->isValidated() < 1) { ?>
					<?php  print $ih->button(t('Mark Email as Valid'), $this->url('/dashboard/users/search?uID=' . intval($uID) . '&task=validate_email'), 'left');?>
					<?php  } ?>
				<?php  } ?>
				
				<?php  if ($uo->getUserID() != USER_SUPER_ID) { ?>
					<?php  if ($uo->isActive()) { ?>
						<?php  print $ih->button(t('Deactivate User'), $this->url('/dashboard/users/search?uID=' . intval($uID) . '&task=deactivate&ccm_token='.$valt->generate('user_deactivate')), 'left');?>
					<?php  } else { ?>
						<?php  print $ih->button(t('Activate User'), $this->url('/dashboard/users/search?uID=' . intval($uID) . '&task=activate&ccm_token='.$valt->generate('user_activate')), 'left');?>
					<?php  } ?>
				<?php  } ?>
			
			<?php  } ?>
			
			<?php 
			$tp = new TaskPermission();
			if ($uo->getUserID() != $u->getUserID()) {
				if ($tp->canSudo()) { 
				
					$loginAsUserConfirm = t('This will end your current session and sign you in as %s', $uo->getUserName());
					
					print $ih->button_js(t('Sign In as User'), 'loginAsUser()', 'left');?>
	
					<script type="text/javascript">
					loginAsUser = function() {
						if (confirm('<?php echo $loginAsUserConfirm?>')) { 
							location.href = "<?php echo $this->url('/dashboard/users/search', 'sign_in_as_user', $uo->getUserID(), $valt->generate('sudo'))?>";				
						}
					}
					</script>
	
				<?php  } /*else { ?>
					<?php  print $ih->button_js(t('Sign In as User'), 'alert(\'' . t('You do not have permission to sign in as other users.') . '\')', 'left', 'ccm-button-inactive');?>
				<?php  }*/ ?>
			<?php  } ?>

		</div>
		
		<h2><?php echo t('Required Information')?></h2>
		
		<div style="margin:0px; padding:0px; width:100%; height:auto" >
		<table border="0" cellspacing="1" cellpadding="0">
		<tr>
			<td><?php echo $av->outputUserAvatar($uo)?></td>
			<td><?php echo $uo->getUserName()?><br/>
			<a href="mailto:<?php echo $uo->getUserEmail()?>"><?php echo $uo->getUserEmail()?></a><br/>
			<?php echo $uo->getUserDateAdded('user')?>
			<?php echo (ENABLE_USER_TIMEZONES && strlen($uo->getUserTimezone())?"<br />".t('Timezone').": ".$uo->getUserTimezone():"")?>
            
			<?php  if (USER_VALIDATE_EMAIL) { ?><br/>
				<?php echo t('Full Record')?>: <strong><?php echo  ($uo->isFullRecord()) ? "Yes" : "No" ?></strong>
				&nbsp;&nbsp;
				<?php echo t('Email Validated')?>: <strong><?php 
					switch($uo->isValidated()) {
						case '-1':
							print t('Unknown');
							break;
						case '0':
							print t('No');
							break;
						case '1':
							print t('Yes');
							break;
					}?>
					</strong>
			<?php  } ?></td>
		</tr>
		</table>
		</div>

		
		<?php 
		$attribs = UserAttributeKey::getList(true);
		if (count($attribs) > 0) { ?>
		<h2><?php echo t('Other Information')?></h2>

		<div style="margin:0px; padding:0px; width:100%; height:auto" >
		<table class="entry-form" border="0" cellspacing="1" cellpadding="0">


		<?php  
		for ($i = 0; $i < count($attribs); $i = $i + 3) { 			
			$uk = $attribs[$i]; 
			$uk2 = $attribs[$i+1]; 
			$uk3 = $attribs[$i+2]; 		
			
			?>
			
		<tr>
			<td class="subheader" style="width: 33%"><?php echo $uk->getAttributeKeyDisplayHandle()?></td>
			<?php  if (is_object($uk2)) { ?><td  style="width: 33%" class="subheader"><?php echo $uk2->getAttributeKeyDisplayHandle()?></td><?php  } else { ?><td  style="width: 33%" class="subheader">&nbsp;</td><?php  } ?>
			<?php  if (is_object($uk3)) { ?><td  style="width: 33%"class="subheader"><?php echo $uk3->getAttributeKeyDisplayHandle()?></td><?php  } else { ?><td style="width: 33%" class="subheader">&nbsp;</td><?php  } ?>
		</tr>
		<tr>
			<td><?php echo $uo->getAttribute($uk->getAttributeKeyHandle(), 'displaySanitized', 'display')?></td>
			<?php  if (is_object($uk2)) { ?><td><?php echo $uo->getAttribute($uk2->getAttributeKeyHandle(), 'displaySanitized', 'display')?></td><?php  } else { ?><td style="width: 33%">&nbsp;</td><?php  } ?>
			<?php  if (is_object($uk3)) { ?><td><?php echo $uo->getAttribute($uk3->getAttributeKeyHandle(), 'displaySanitized', 'display')?></td><?php  } else { ?><td>&nbsp;</td><?php  } ?>
		</tr>
		<?php  } ?>
		
		</table>
		</div>
		
		<?php  }  ?>
		
		<h2><?php echo t('Groups')?></h2>

		<div style="margin:0px; padding:0px; width:100%; height:auto" >
		
		<table class="entry-form" border="0" cellspacing="1" cellpadding="0">
		<tr>
			<td colspan="2" class="header"><?php echo t('Group')?></td>
			<td class="header"><?php echo t('Date Entered')?></td>
		</tr>
		<?php  $gArray = $gl->getGroupList(); ?>
		<tr>
			<td colspan="2">
				<?php  $enteredArray = array(); ?>
				<?php  foreach ($gArray as $g) { ?>
					<?php  if ($g->inGroup()) {
						echo($g->getGroupName() . '<br>');
						$enteredArray[] = $g->getGroupDateTimeEntered();
					} ?>
				<?php  } ?>
			</td>
			<td>
			<?php  foreach ($enteredArray as $dateTime) {
				if ($dateTime != '0000-00-00 00:00:00') {
					echo($dateTime . '<br>');
				} else {
					echo('<br>');
				}
			} ?>
			</td>
		</tr>
		</table>
		</div>
	</div>

	<h1><span><?php echo t('Delete User')?></span></h1>
	
	<div class="ccm-dashboard-inner">
		<div class="ccm-spacer"></div>
		<?php 
		$cu = new User();
		$tp = new TaskPermission();
		if ($tp->canDeleteUser()) {
		$delConfirmJS = t('Are you sure you want to permanently remove this user?');
			if ($uo->getUserID() == USER_SUPER_ID) { ?>
				<?php echo t('You may not remove the super user account.')?>
			<?php  } else if (!$tp->canDeleteUser()) { ?>
				<?php echo t('You do not have permission to perform this action.');		
			} else if ($uo->getUserID() == $cu->getUserID()) {
				echo t('You cannot delete your own user account.');
			}else{ ?>   
				
				<script type="text/javascript">
				deleteUser = function() {
					if (confirm('<?php echo $delConfirmJS?>')) { 
						location.href = "<?php echo $this->url('/dashboard/users/search', 'delete', $uo->getUserID(), $valt->generate('delete_account'))?>";				
					}
				}
				</script>
	
				<?php  print $ih->button_js(t('Delete User Account'), "deleteUser()", 'left');?>
	
			<?php  }
		} else {
			echo t('You do not have permission to perform this action.');
		}?>
		<div class="ccm-spacer"></div>
	</div>
	<?php  } ?>


<script type="text/javascript">


ccm_activateEditableProperties = function() {
	$("tr.ccm-attribute-editable-field").each(function() {
		var trow = $(this);
		$(this).find('a').click(function() {
			trow.find('.ccm-attribute-editable-field-text').hide();
			trow.find('.ccm-attribute-editable-field-clear-button').hide();
			trow.find('.ccm-attribute-editable-field-form').show();
			trow.find('.ccm-attribute-editable-field-save-button').show();
		});
		
		trow.find('form').submit(function() {
			ccm_submitEditableProperty(trow);
			return false;
		});
		
		trow.find('.ccm-attribute-editable-field-save-button').parent().click(function() {
			ccm_submitEditableProperty(trow);
		});

		trow.find('.ccm-attribute-editable-field-clear-button').parent().unbind();
		trow.find('.ccm-attribute-editable-field-clear-button').parent().click(function() {
			trow.find('form input[name=task]').val('clear_extended_attribute');
			ccm_submitEditableProperty(trow);
			return false;
		});

	});
}

ccm_submitEditableProperty = function(trow) {
	trow.find('.ccm-attribute-editable-field-save-button').hide();
	trow.find('.ccm-attribute-editable-field-clear-button').hide();
	trow.find('.ccm-attribute-editable-field-loading').show();
	try {
		tinyMCE.triggerSave(true, true);
	} catch(e) { }
	
	trow.find('form').ajaxSubmit(function(resp) {
		// resp is new HTML to display in the div
		trow.find('.ccm-attribute-editable-field-loading').hide();
		trow.find('.ccm-attribute-editable-field-save-button').show();
		trow.find('.ccm-attribute-editable-field-text').html(resp);
		trow.find('.ccm-attribute-editable-field-form').hide();
		trow.find('.ccm-attribute-editable-field-save-button').hide();
		trow.find('.ccm-attribute-editable-field-text').show();
		trow.find('.ccm-attribute-editable-field-clear-button').show();
		trow.find('td').show('highlight', {
			color: '#FFF9BB'
		});

	});
}

$(function() {
	ccm_activateEditableProperties();
	$("#groupSelector").dialog();
	ccm_triggerSelectGroup = function(gID, gName) {
		var html = '<input type="checkbox" name="gID[]" value="' + gID + '" style="vertical-align: middle" checked /> ' + gName + '<br/>';
		$("#ccm-additional-groups").append(html);
	}

});
</script>


<?php 

} else { ?>

<h1><span><?php echo t('User Search')?></span></h1>

<div class="ccm-dashboard-inner">

	<?php 
	$tp = new TaskPermission();
	if ($tp->canAccessUserSearch()) { 
	
	?>

	<table id="ccm-search-form-table" >
		<tr>
			<td valign="top" class="ccm-search-form-advanced-col">
				<?php  Loader::element('users/search_form_advanced'); ?>
			</td>		

			<td valign="top" width="100%">	
				
				<div id="ccm-search-advanced-results-wrapper">
					
					<div id="ccm-user-search-results">
					
						<?php  Loader::element('users/search_results', array('users' => $users, 'userList' => $userList, 'pagination' => $pagination)); ?>
					
					</div>
				
				</div>
			
			</td>	
		</tr>
	</table>		

	<?php  } else { ?>
		<p><?php echo t('You do not have access to user search. This setting may be changed in the access section of the dashboard settings page.')?></p>
	<?php  } ?>
	
</div>

<?php  } ?>