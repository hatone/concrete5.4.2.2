<?php  defined('C5_EXECUTE') or die("Access Denied."); ?>
<div class="ccm-paging-top"><?php echo t('Viewing <b>%s</b> to <b>%s</b> (<b>%s</b> Total)', $pOptions['currentRangeStart'], "<span id=\"pagingPageResults\">" . $pOptions['currentRangeEnd'] . "</span>", "<span id=\"pagingTotalResults\">" . $pOptions['total'] . "</span>")?></div>


