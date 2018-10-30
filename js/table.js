function makeTableSortable(element) {
	$(element)
		// Initialize tablesorter
		// ***********************
		.tablesorter({
			theme: 'blue',
			sortList: [[6,1], [5,1]],
			widthFixed: true,
			widgets: ['zebra', 'filter', 'pager' ],

			widgetOptions: {

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
				pager_fixedHeight: true,

				// remove rows from the table to speed up the sort of large tables.
				// setting this to false, only hides the non-visible rows; needed if you plan to add/remove rows with the pager enabled.
				pager_removeRows: false // removing rows in larger tables speeds up the sort

			}

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
}
