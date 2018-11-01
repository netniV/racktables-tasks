<?php

foreach(glob(__DIR__ . '/renderers/tasks*.php') as $filename) {
	require_once($filename);
}

function renderTasksEditField($isViewOnly, $isVertical, $id, $label, $entityView, $entityEdit, $columns = 0, $class = '', $style = '') {
	if ($isVertical) {
		if (!empty($label)) {
			$label .= ':';
		}

		echo '<tr><td width="50%" class="tdright"><b>' . $label . '</b></td>';
	}

	$colSpan = ($columns > 0) ? ' colspan="' .  $columns . '"' : '';
	$colId   = ($id > '') ? ' id="' . $id . '"' : '';
	echo '<td' . $colId . $colSpan . ' class="tdleft ' . $class . '" ' . (empty($style) ? '' : "style='$style' ") . '>' .
		($isViewOnly ? $entityView : $entityEdit) . '</td>';

	if ($isVertical) {
		echo '</tr>';
	}
}

function renderJSLinks() {
	static $isJSLinksRendered = false;

	if (!$isJSLinksRendered) {
		$isJSLinksRendered = true;

		global $remote_username;
		addJS(<<<END
function getTaskUser() {
	return '{$remote_username}';
}
END
		,TRUE);

		addJS('https://code.jquery.com/jquery-3.3.1.min.js');
		addJS('https://code.jquery.com/ui/1.12.1/jquery-ui.min.js');
		addJS('https://cdn.jsdelivr.net/npm/flatpickr');

		addJS('tasks/js/jquery.tablesorter.js');
		addJS('tasks/js/jquery.tablesorter.widgets.js');
		addJS('tasks/js/widget-pager.js');
		addJS('tasks/js/table.js');

		addCSS('https://code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css');
		addCSS('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
		addCSS('https://npmcdn.com/flatpickr/dist/themes/material_blue.css');
		addCSS('https://use.fontawesome.com/releases/v5.4.2/css/all.css');

		addCSS('tasks/css/tasks.css');
		addCSS('tasks/css/jquery.tablesorter.pager.css');
		addCSS('tasks/css/themes/blue/style.css');
	}
}

