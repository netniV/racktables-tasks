function makeTableSortable(element, sort, usePager) {
	tableEl = $(element);
	if (tableEl.length === 0)
		return;

	debugger;
	defaultSort = tableEl.data('task-filter');
	if (typeof defaultSort === 'undefined' || defaultSort == null || (typeof defaultSort.replace != 'undefined' && defaultSort.replace(/\s/g, '').length < 1)) {
		if (typeof sort == 'undefined') {
			sort = [[7,0],[8,1]];
		}
	} else {
		sort = defaultSort;
	}

	if (typeof usePager == 'undefined') {
		usePager = true;
	}

	pagerOptions = { };
	widgetOptions = [ 'zebra' ];

	if (usePager) {
		widgetOptions = [ 'zebra', 'filter', 'pager' ];

		pagerOptions = {
			// ** NOTE: All default ajax options have been removed from this demo,
			// see the example-widget-pager-ajax demo for a full list of pager
			// options

			// css class names that are added
			pager_css: {
				container   : 'tablesorter-pager',    // class added to make included pager.css file work
				errorRow    : 'tablesorter-errorRow', // error information row (don't include period at beginning); styled in theme file
				disabled    : 'disabled'              // class added to arrows @ extremes (i.e. prev/first arrows "disabled" on first page)
			},

			// jQuery selectors
			pager_selectors: {
				container   : '.pager',       // target the pager markup (wrapper)
				first       : '.first',       // go to first page arrow
				prev        : '.prev',        // previous page arrow
				next        : '.next',        // next page arrow
				last        : '.last',        // go to last page arrow
				gotoPage    : '.gotoPage',    // go to page selector - select dropdown that sets the current page
				pageDisplay : '.pagedisplay', // location of where the "output" is displayed
				pageSize    : '.pagesize'     // page size selector - select dropdown that sets the "size" option
			},

			// output default: '{page}/{totalPages}'
			// possible variables: {size}, {page}, {totalPages}, {filteredPages}, {startRow}, {endRow}, {filteredRows} and {totalRows}
			// also {page:input} & {startRow:input} will add a modifiable input in place of the value
			pager_output: '{startRow:input} &ndash; {endRow} / {totalRows} rows', // '{page}/{totalPages}'

			// apply disabled classname to the pager arrows when the rows at either extreme is visible
			pager_updateArrows: true,

			// starting page of the pager (zero based index)
			pager_startPage: 0,

			// Reset pager to this page after filtering; set to desired page number
			// (zero-based index), or false to not change page at filter start
			pager_pageReset: 0,

			// Number of visible rows
			pager_size: 10,

			// f true, child rows will be counted towards the pager set size
			pager_countChildRows: false,

			// Save pager page & size if the storage script is loaded (requires $.tablesorter.storage in jquery.tablesorter.widgets.js)
			pager_savePages: true,

			// Saves tablesorter paging to custom key if defined. Key parameter name
			// used by the $.tablesorter.storage function. Useful if you have
			// multiple tables defined
			pager_storageKey: "tablesorter-pager",

			// if true, the table will remain the same height no matter how many records are displayed. The space is made up by an empty
			// table row set to a height to compensate; default is false
			pager_fixedHeight: false,

			// remove rows from the table to speed up the sort of large tables.
			// setting this to false, only hides the non-visible rows; needed if you plan to add/remove rows with the pager enabled.
			pager_removeRows: false // removing rows in larger tables speeds up the sort
		}
	} else {
		$('.pager').hide();
	}

	tableEl
		// Initialize tablesorter
		// ***********************
		.tablesorter({
			theme: 'blue',
			sortList: sort,
			cssInfoBlock : "newrow",
			widthFixed: true,
			widgets: widgetOptions,
			widgetOptions: pagerOptions
		})

		// bind to pager events
		// *********************
		.bind('pagerChange pagerComplete pagerInitialized pageMoved', function(e, c) {
			var p = c.pager, // NEW with the widget... it returns config, instead of config.pager
				msg = '"</span> event triggered, ' + (e.type === 'pagerChange' ? 'going to' : 'now on') +
				' page <span class="typ">' + (p.page + 1) + '/' + p.totalPages + '</span>';
			$('#display')
				.append('<li><span class="str">"' + e.type + msg + '</li>')
				.find('li:first').remove();
		});

	$('[id^="task_complete_"]').hover(
		function() {
			$(this).addClass('fas fa-check').removeClass('far fa-circle');
		},
		function() {
			$(this).addClass('far fa-circle').removeClass('fas fa-check');
		}
	).click(function() {
		var task = $(this);
		var taskId = task[0].id.replace('task_complete_','');
		var taskDialog = $('#task_'  + taskId + '_dialog');

		var paramPage = 'tasks';
		var paramTab = 'default';
		var paramObjectId = 0;

		var paramResults = new RegExp('[\?&]page=([^&#]*)').exec(window.location.href);
		if (paramResults!=null) {
			paramPage = decodeURI(paramResults[1]) || 'tasks';
		}

		var paramResults = new RegExp('[\?&]tab=([^&#]*)').exec(window.location.href);
		if (paramResults!=null) {
			paramTab = decodeURI(paramResults[1]) || 'default';
		}
		var paramOp = (paramPage == 'object' && paramTab == 'default') ? 'tic' : 'upd';
		var paramUrl = 'page=' + paramPage + '&tab=' + paramTab;
		var paramResults = new RegExp('[\?&]object_id=([^&#]*)').exec(window.location.href);
		if (paramResults!=null) {
			paramObjectId = decodeURI(paramResults[1]) || 0;
			if (paramObjectId) {
				paramUrl = paramUrl + '&object_id='  + paramObjectId;
			}
		}
		paramUrl = paramUrl + '&op=' + paramOp;

		$('#tasksContainer').remove();
		$('body').append('<div id="tasksContainer" style="display:none"><form method=post id=upd name=upd action="?module=redirect&' + paramUrl + '"><input type=hidden name="id" value="' + taskId + '"><input type=hidden name="completed" value="yes"></form></div>');
		$('#tasksContainer form').append(taskDialog.html());

		var taskName = $("#tasksContainer input[name=completed_by]");
		var taskTime = $("#tasksContainer input[name=completed_time]");
		var taskNote = $("#tasksContainer textarea[name=notes]");

		taskName.val(getTaskUser());
		taskTime.val(getTaskDate());
		taskTime.addClass('tasks-datetime');
		taskTime.flatpickr(getDateTimePickerDefaults());

		var messageWidth = $(window).width();
		if (messageWidth > 600) {
			messageWidth = 600;
		} else {
			messageWidth -= 50;
		}

		$('#tasksContainer').dialog({
			open: function() {
				$("#tasksContainer textarea").focus();
			},
			modal: true,
			height: 'auto',
			minWidth: messageWidth,
			maxWidth: 800,
			maxHeight: 600,
			title: 'Task Completion',
			buttons: {
				'Ok' : {
					text: 'OK',
					id: 'btnTaskContainerOK',
					click: function() {
						$("#tasksContainer form").submit();
					}
				},
				Cancel : function() {
					$(this).dialog('close');
				},
			}
		});
	});
}

function getZeroPrefix(d) {
	return (d < 10 ? '0' : '') + d;
}

function getTaskDate() {
	var d = new Date();
	var c = d.getFullYear() + '-' + getZeroPrefix(d.getMonth() + 1) + '-' + getZeroPrefix(d.getDate()) + ' ' +
		getZeroPrefix(d.getHours()) + ':' + getZeroPrefix(d.getMinutes()) + ':' + getZeroPrefix(d.getSeconds());
	return c;
}

function getDateTimePickerDefaults() {
	return {
		enableTime: true,
		dateFormat: 'Y-m-d H:i:S',
		altInput: true,
		altFormat: 'M j, Y H:i:S',
		time_24hr: true,
	};
}

function makeDateTimePickers() {
	$('.tasks-datetime').flatpickr(getDateTimePickerDefaults());
}

$(function() {
	makeDateTimePickers();
	$('select[name=completed]').change(function() {

		var completed = $(this).val() == 'yes';
		var c, u;
		if (completed) {
			c = getTaskDate();
			u = getTaskUser();
		} else {
			c = '';
			u = '';
		}

		var parent = $(this).parent().parent().parent();
		var taskTime = parent.find('input[name=completed_time]');
		var taskName = parent.find('input[name=completed_by]');

		taskName.val(u);
		taskTime.val(c);
		var f = taskTime.flatpickr(getDateTimePickerDefaults());
		f.setDate(c);
	});

	makeTableSortable('#taskstable', [[9, 1]]);
	makeTableSortable('#tasksitemtable', undefined, false);
	makeTableSortable('#tasksdefinitiontable', [[1,0]]);
	makeTableSortable('#tasksfrequencytable', [[1,0]]);
});
