<?php 
defined('C5_EXECUTE') or die("Access Denied.");
global $c;
Loader::model('collection_types');
Loader::model('collection_attributes');
$dt = Loader::helper('form/date_time');
$uh = Loader::helper('form/user_selector');

if ($cp->canAdminPage()) {
	$ctArray = CollectionType::getList();
}
?>
<div class="ccm-pane-controls">
<form method="post" name="permissionForm" id="ccmMetadataForm" action="<?php echo $c->getCollectionAction()?>">
<input type="hidden" name="rel" value="<?php echo $_REQUEST['rel']?>" />

	<script type="text/javascript"> 
		
		function ccm_triggerSelectUser(uID, uName) {
			$('#ccm-uID').val(uID);
			$('#ccm-uName').html(uName);
		}
		
		
		var ccm_activePropertiesTab = "ccm-properties-standard";
		
		$("#ccm-properties-tabs a").click(function() {
			$("li.ccm-nav-active").removeClass('ccm-nav-active');
			$("#" + ccm_activePropertiesTab + "-tab").hide();
			ccm_activePropertiesTab = $(this).attr('id');
			$(this).parent().addClass("ccm-nav-active");
			$("#" + ccm_activePropertiesTab + "-tab").show();
		});
		
		ccm_settingsSetupCacheForm = function() {
			var obj = $('input[name=cCacheFullPageContent]:checked');
			if (obj.attr('enable-cache') == 1) {
				$('div.ccm-properties-cache-lifetime input').attr('disabled', false);
			} else {
				$('div.ccm-properties-cache-lifetime input').attr('disabled', true);
				$('input[name=cCacheFullPageContentOverrideLifetime][value=0]').attr('checked', true);
			}

			var obj2 = $('input[name=cCacheFullPageContentOverrideLifetime]:checked');
			if (obj2.val() == 'custom') {
				$('input[name=cCacheFullPageContentLifetimeCustom]').attr('disabled', false);
			} else {
				$('input[name=cCacheFullPageContentLifetimeCustom]').attr('disabled', true);
				$('input[name=cCacheFullPageContentLifetimeCustom]').val('');
			}

		}
		
		$(function() {
			$("input[name=cCacheFullPageContent]").click(function() {
				ccm_settingsSetupCacheForm();
			});
			$("input[name=cCacheFullPageContentOverrideLifetime]").click(function() {
				ccm_settingsSetupCacheForm();
			});
			$("input[name=cCacheFullPageContentOverrideLifetime][value=custom]").click(function() {
				$('input[name=cCacheFullPageContentLifetimeCustom]').get(0).focus();
			});
			ccm_settingsSetupCacheForm();
			$("#ccmMetadataForm").ajaxForm({
				type: 'POST',
				iframe: true,
				beforeSubmit: function() {
					jQuery.fn.dialog.showLoader();
				},
				success: function(r) {
					try {
						var r = eval('(' + r + ')');
						if (r != null && r.rel == 'SITEMAP') {
							jQuery.fn.dialog.hideLoader();
							jQuery.fn.dialog.closeTop();
							ccmSitemapHighlightPageLabel(r.cID, r.name);
						} else {
							ccm_mainNavDisableDirectExit();
							ccm_hidePane(function() {
								jQuery.fn.dialog.hideLoader();						
							});
						}
						ccmAlert.hud(ccmi18n.savePropertiesMsg, 2000, 'success', ccmi18n.properties);
					} catch(e) {
						alert(r);
					}
				}
			});
		});
	</script>
	
	<style type="text/css">
	.ccm-field-meta #newAttrValueRows{ margin-top:4px; }
	.ccm-field-meta .newAttrValueRow{margin-top:4px}	
	.ccm-field-meta input.faint{ color:#999 }
	
	#ccm-properties-custom-tab, #ccm-properties-standard-tab, #ccm-page-paths-tab {
		margin-top: 12px;
	}
	</style>
	
	<h1><?php echo t('Page Properties')?></h1>

	
	<div id="ccm-required-meta">
	
		
	<ul class="ccm-dialog-tabs" id="ccm-properties-tabs">
		<li class="ccm-nav-active"><a href="javascript:void(0)" id="ccm-properties-standard"><?php echo t('Standard Properties')?></a></li>
		<li><a href="javascript:void(0)" id="ccm-page-paths"><?php echo t('Page Paths and Location')?></a></li>
		<li><a href="javascript:void(0)" id="ccm-properties-custom"><?php echo t('Custom Attributes')?></a></li>
		<li><a href="javascript:void(0)" id="ccm-properties-cache"><?php echo t('Speed Settings')?></a></li>
	</ul>

	<div id="ccm-properties-standard-tab">
	
	<div class="ccm-field-one">
	<label><?php echo t('Name')?></label> <input type="text" name="cName" value="<?php echo htmlentities( $c->getCollectionName(), ENT_QUOTES, APP_CHARSET) ?>" class="ccm-input-text">
	</div>
	
	<div class="ccm-field-one">
	
	<label><?php echo t('Public Date/Time')?></label> 
	<?php  
	print $dt->datetime('cDatePublic', $c->getCollectionDatePublic(null, 'user')); ?>
	</div>
	
	<div class="ccm-field-two">
	<label><?php echo t('Owner')?></label>
	
		<?php  
		print $uh->selectUser('uID', $c->getCollectionUserID());
		?>
		
	</div>
		
	
	<div class="ccm-field">
	<label><?php echo t('Description')?></label> <textarea name="cDescription" class="ccm-input-text" style="width: 570px; height: 50px"><?php echo $c->getCollectionDescription()?></textarea>
	</div>
	
	</div>
	
	<div id="ccm-page-paths-tab" style="display: none">
		
		<div class="ccm-field">
		<label><?php echo  t('Canonical URL')?></label>
		<?php  if (!$c->isGeneratedCollection()) { ?>
			<?php echo BASE_URL . DIR_REL;?><?php  if (URL_REWRITING == false) { ?>/<?php echo DISPATCHER_FILENAME?><?php  } ?><?php 
			$cPath = substr($c->getCollectionPath(), strrpos($c->getCollectionPath(), '/') + 1);
			print substr($c->getCollectionPath(), 0, strrpos($c->getCollectionPath(), '/'))?>/<input type="text" name="cHandle" class="ccm-input-text" value="<?php  echo $cPath?>" id="cHandle"><input type="hidden" name="oldCHandle" id="oldCHandle" value="<?php  echo $c->getCollectionHandle()?>"><br /><br />
		<?php   } else { ?>
			<?php  echo $c->getCollectionHandle()?><br /><br />
		<?php   } ?>
		<div class="ccm-note"><?php echo t('This page must always be available from at least one URL. That URL is listed above.')?></div>
		</div>

		<?php  if (!$c->isGeneratedCollection()) { ?>
			<label><?php  echo  t('Additional Page URL(s)') ?></label>
			<p><?php  echo t('Remember to include a slash before the page name, for example \'/my-alternative-url\'.');?></p>
	
			<div class="ccm-field">
			<?php 
				$paths = $c->getPagePaths();
				foreach ($paths as $path) {
					if (!$path['ppIsCanonical']) {
						$ppID = $path['ppID'];
						$cPath = $path['cPath'];
						echo '<span class="ccm-meta_path">' .
			     			'<input type="text" name="ppURL-' . $ppID . '" class="ccm-input-text" value="' . $cPath . '" id="ppID-'. $ppID . '"> ' .
			     			'<a href="javascript:void(0)" class="ccm-meta-path-del">' . t('Remove Path') . '</a></span>'."\n";
					}
				}
			?>
		    <span class="ccm-meta-path">
	     		<?php  echo BASE_URL . DIR_REL;?><?php   if (URL_REWRITING == false) { ?>/<?php  echo DISPATCHER_FILENAME?><?php   } ?><?php  
			print substr($c->getCollectionPath(), 0, strrpos($c->getCollectionPath(), '/'))?> <input type="text" name="ppURL-add-0" class="ccm-input-text" value="" id="ppID-add-0">
		 		<a href="javascript:void(0)" class="ccm-meta-path-add"><?php echo t('Add Path')?></a>
			</span>
			</div>
		<?php  } ?>
	
	</div>
	
	<div id="ccm-properties-custom-tab" style="display: none">
		<?php  Loader::element('collection_metadata_fields', array('c'=>$c ) ); ?>
	</div>

	<div id="ccm-properties-cache-tab" style="display: none">
		<h2><?php echo t('Full Page Caching')?></h2>
		<?php  if (!ENABLE_CACHE) {
			print t('The cache has been disabled. Full page caching is not available.');
		} else { ?>
			<div>
			<?php  $form = Loader::helper('form');?>
			<?php 
			switch(FULL_PAGE_CACHE_GLOBAL) {
				case 'blocks':
					$globalSetting = t('cache page if all blocks support it.');
					$enableCache = 1;
					break;
				case 'all':
					$globalSetting = t('enable full page cache.');
					$enableCache = 1;
					break;
				case 0:
					$globalSetting = t('disable full page cache.');
					$enableCache = 0;
					break;
			}
			switch(FULL_PAGE_CACHE_LIFETIME) {
				case 'default':
					$globalSettingLifetime = t('%s minutes', CACHE_LIFETIME / 60);
					break;
				case 'custom':
					$custom = Config::get('FULL_PAGE_CACHE_LIFETIME_CUSTOM');
					$globalSettingLifetime = t('%s minutes', $custom);
					break;
				case 'forever':
					$globalSettingLifetime = t('Until manually cleared');
					break;
			}
			?>
			<div><?php echo $form->radio('cCacheFullPageContent', -1, $c->getCollectionFullPageCaching(), array('enable-cache' => $enableCache))?> <?php echo t('Use global setting - %s', $globalSetting)?></div>
			<div><?php echo $form->radio('cCacheFullPageContent', 0, $c->getCollectionFullPageCaching(), array('enable-cache' => 0))?> <?php echo t('Do not cache this page.')?></div>
			<div><?php echo $form->radio('cCacheFullPageContent', 1, $c->getCollectionFullPageCaching(), array('enable-cache' => 1))?> <?php echo t('Cache this page.')?></div>
			
			<br/>
			
			<h3><?php echo t('Cache for how long?')?></h3>
			
			<div class="ccm-properties-cache-lifetime">
				<?php  $val = ($c->getCollectionFullPageCachingLifetimeCustomValue() > 0 && $c->getCollectionFullPageCachingLifetime()) ? $c->getCollectionFullPageCachingLifetimeCustomValue() : ''; ?>
				<div><?php echo $form->radio('cCacheFullPageContentOverrideLifetime', 0, $c->getCollectionFullPageCachingLifetime())?> <?php echo t('Use global setting - %s', $globalSettingLifetime)?></div>
				<div><?php echo $form->radio('cCacheFullPageContentOverrideLifetime', 'default', $c->getCollectionFullPageCachingLifetime())?> <?php echo t('Default - %s minutes', CACHE_LIFETIME / 60)?></div>
				<div><?php echo $form->radio('cCacheFullPageContentOverrideLifetime', 'custom', $c->getCollectionFullPageCachingLifetime())?> <?php echo t('Custom')?>
					<?php echo $form->text('cCacheFullPageContentLifetimeCustom', $val, array('style' => 'width: 40px'))?> <?php echo t('minutes')?>		
				</div>
				<div><?php echo $form->radio('cCacheFullPageContentOverrideLifetime', 'forever', $c->getCollectionFullPageCachingLifetime())?> <?php echo t('Until manually cleared')?></div>
			</div>
		<?php  } ?>
	</div>
	
	
	<input type="hidden" name="update_metadata" value="1" />
	<input type="hidden" name="processCollection" value="1">
	<div class="ccm-spacer">&nbsp;</div>
</form>
</div>
	<div class="ccm-buttons">
<!--	<a href="javascript:void(0)" onclick="ccm_hidePane()" class="ccm-button-left cancel"><span><em class="ccm-button-close">Cancel</em></span></a>//-->
	<a href="javascript:void(0)" onclick="$('#ccmMetadataForm').submit()" class="ccm-button-right accept"><span><?php echo t('Save')?></span></a>
	</div>
