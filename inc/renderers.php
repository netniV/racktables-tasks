<?php

foreach(glob(__DIR__ . '/renderers/tasks*.php') as $filename) {
	require_once($filename);
}

function renderTasksEditField($isViewOnly, $isVertical, $label, $entityView, $entityEdit, $columns = 0, $class = '', $style = '') {
	if ($isVertical) {
		if (!empty($label)) {
			$label .= ':';
		}
		echo '<tr><td width="50%" class="tdright"><b>' . $label . '</b></td>';
	}

	$colSpan = ($columns > 0) ? ' colspan="' .  $columns . '"' : '';
	echo '<td' . $colSpan . ' class="tdleft ' . $class . '" ' . (empty($style) ? '' : "style='$style' ") . '>' . ($isViewOnly ? $entityView : $entityEdit) . '</td>';

	if ($isVertical) {
		echo '</tr>';
	}
}
