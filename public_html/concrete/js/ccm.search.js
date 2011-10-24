var ccm_searchActivatePostFunction = new Array();

ccm_setupAdvancedSearchFields = function(searchType) {
	ccm_totalAdvancedSearchFields = $('.ccm-search-request-field-set').length;
	$("#ccm-" + searchType + "-search-add-option").unbind();
	$("#ccm-" + searchType + "-search-add-option").click(function() {
		ccm_totalAdvancedSearchFields++;
		$("#ccm-search-fields-wrapper").append('<div class="ccm-search-field" id="ccm-' + searchType + '-search-field-set' + ccm_totalAdvancedSearchFields + '">' + $("#ccm-search-field-base").html() + '<\/div>');
		ccm_activateAdvancedSearchFields(searchType, ccm_totalAdvancedSearchFields);
	});
	
	// we have to activate any of the fields that were here based on the request
	// these fields show up after a page is reloaded but we want to keep showing the request fields
	var i = 1;
	$('.ccm-search-request-field-set').each(function() {
		ccm_activateAdvancedSearchFields(searchType, i);
		i++;
	});
}

ccm_setupAdvancedSearch = function(searchType) {
	ccm_setupAdvancedSearchFields(searchType);
	$("#ccm-" + searchType + "-advanced-search").ajaxForm({
		beforeSubmit: function() {
			ccm_deactivateSearchResults(searchType);
		},
		
		success: function(resp) {
			ccm_parseAdvancedSearchResponse(resp, searchType);
		}
	});
	ccm_setupInPagePaginationAndSorting(searchType);
	ccm_setupSortableColumnSelection(searchType);
	
}

ccm_parseAdvancedSearchResponse = function(resp, searchType) {
	var obj = $("#ccm-" + searchType + "-search-results");
	if (obj.length == 0 || searchType == null) {
		obj = $("#ccm-search-results");
	}
	obj.html(resp);
	ccm_activateSearchResults(searchType);
}

ccm_deactivateSearchResults = function(searchType) {
	var obj = $("#ccm-" + searchType + "-search-fields-submit");
	if (obj.length == 0 || searchType == null) {
		obj = $("#ccm-search-fields-submit");
	}
	obj.attr('disabled', true);
	var obj = $("#ccm-" + searchType + "-search-loading");
	if (obj.length == 0 || searchType == null) {
		obj = $("#ccm-search-loading");
	}
	obj.show();
}

ccm_activateSearchResults = function(searchType) {
	var obj = $("#ccm-" + searchType + "-search-loading");
	if (obj.length == 0 || searchType == null) {
		obj = $("#ccm-search-loading");
	}
	obj.hide();
	var obj = $("#ccm-" + searchType + "-search-fields-submit");
	if (obj.length == 0 || searchType == null) {
		obj = $("#ccm-search-fields-submit");
	}
	obj.attr('disabled', false);
	ccm_setupInPagePaginationAndSorting(searchType);
	ccm_setupSortableColumnSelection(searchType);
	if(typeof(ccm_searchActivatePostFunction[searchType]) == 'function') {
		ccm_searchActivatePostFunction[searchType]();
	}
}

ccm_setupInPagePaginationAndSorting = function(searchType) {
	$(".ccm-results-list th a").click(function() {
		ccm_deactivateSearchResults(searchType);
		var obj = $("#ccm-" + searchType + "-search-results");
		if (obj.length == 0) {
			obj = $("#ccm-search-results");
		}
		obj.load($(this).attr('href'), false, function() {
			ccm_activateSearchResults(searchType);
		});
		return false;
	});
	$("div.ccm-pagination a").click(function() {
		ccm_deactivateSearchResults(searchType);
		var obj = $("#ccm-" + searchType + "-search-results");
		if (obj.length == 0) {
			obj = $("#ccm-search-results");
		}
		obj.load($(this).attr('href'), false, function() {
			ccm_activateSearchResults(searchType);
			$("div.ccm-dialog-content").attr('scrollTop', 0);
		});
		return false;
	});
}

ccm_setupSortableColumnSelection = function(searchType) {
	$("#ccm-search-add-column").unbind();
	$("#ccm-search-add-column").click(function() {
		jQuery.fn.dialog.open({
			width: 550,
			height: 350,
			modal: false,
			href: $(this).attr('href'),
			title: ccmi18n.customizeSearch				
		});
		return false;
	});
}

ccm_checkSelectedAdvancedSearchField = function(searchType, fieldset) {
	$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-search-option[search-field=date_added] input").each(function() {
		if ($(this).attr('id') == 'date_from') {
			$(this).attr('id', 'date_from' + fieldset);
		} else if ($(this).attr('id') == 'date_to') {
			$(this).attr('id', 'date_to' + fieldset);
		}
	});

	$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-search-option input.ccm-input-date").each(function() {
		$(this).attr('id', $(this).attr('id') + fieldset);
	});
	
	
	$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-search-option[search-field=date_added] input").datepicker({
		showAnim: 'fadeIn'
	});
	$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-search-option-type-date_time input").datepicker({
		showAnim: 'fadeIn'
	});
	$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-search-option-type-rating input").rating();		
}

ccm_activateAdvancedSearchFields = function(searchType, fieldset) {
	var selTag = $("#ccm-" + searchType + "-search-field-set" + fieldset + " select:first");
	selTag.unbind();
	selTag.change(function() {
		var selected = $(this).find(':selected').val(); 
		$(this).next('input.ccm-' + searchType + '-selected-field').val(selected);
		
		var itemToCopy = $('#ccm-' + searchType + '-search-field-base-elements span[search-field=' + selected + ']');
		$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-selected-field-content").html('');
		itemToCopy.clone().appendTo("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-selected-field-content");
		
		$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-selected-field-content .ccm-search-option").show();
		ccm_checkSelectedAdvancedSearchField(searchType, fieldset);
	});

	
	// add the initial state of the latest select menu
	/*
	var lastSelect = $("#ccm-" + searchType + "-search-field-set" + fieldset + " select[ccm-advanced-search-selector=1]").eq($(".ccm-" + searchType + "-search-field select[ccm-advanced-search-selector=1]").length-1);
	var selected = lastSelect.find(':selected').val();
	lastSelect.next('input.ccm-" + searchType + "-selected-field').val(selected);
	*/
	
	$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-search-remove-option").unbind();
	$("#ccm-" + searchType + "-search-field-set" + fieldset + " .ccm-search-remove-option").click(function() {
		$(this).parents('div.ccm-search-field').remove();
		//ccm_totalAdvancedSearchFields--;
	});
	
	ccm_checkSelectedAdvancedSearchField(searchType, fieldset);
	
}


ccm_activateEditablePropertiesGrid = function() {
	$("tr.ccm-attribute-editable-field").each(function() {
		var trow = $(this);
		$(this).find('a').click(function() {
			trow.find('.ccm-attribute-editable-field-text').hide();
			trow.find('.ccm-attribute-editable-field-clear-button').hide();
			trow.find('.ccm-attribute-editable-field-form').show();
			trow.find('.ccm-attribute-editable-field-save-button').show();
		});
		
		trow.find('form').submit(function() {
			ccm_submitEditablePropertiesGrid(trow);
			return false;
		});
		
		trow.find('.ccm-attribute-editable-field-save-button').parent().click(function() {
			ccm_submitEditablePropertiesGrid(trow);
		});

		trow.find('.ccm-attribute-editable-field-clear-button').parent().unbind();
		trow.find('.ccm-attribute-editable-field-clear-button').parent().click(function() {
			trow.find('form input[name=task]').val('clear_extended_attribute');
			ccm_submitEditablePropertiesGrid(trow);
			return false;
		});

	});
}

ccm_submitEditablePropertiesGrid = function(trow) {

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


