<?php

function renderTasksFrequenciesGlobals () {
	static $isRenderedTasksFrequenciesGlobal = false;

	if (!$isRenderedTasksFrequenciesGlobal) {
		$isRenderedTasksFrequenciesGlobal = true;

		renderJSLinks();
	}
}

function renderTasksFrequencies ($object_id)
{
	renderTasksFrequenciesGlobals ();

	function printNewItemTR ()
	{
		echo '<tbody class="newrow">';
		printOpFormIntro ('add');
		echo '<tr>' .
			'<td>' . getImageHREF ('create', 'add a new frequency', TRUE) . '</td>' .
			'<td><input type=text size=24 name=name></td>';

		if (getConfigVar ('TASKS_HIDE_ID') != 'yes') {
			echo '<td>-</td>';
		}

		echo	'<td><input type=text size=48 name=format></td>' .
			'<td>&nbsp;</td>' .
			'<td>' . getImageHREF ('create', 'add a new frequency', TRUE) . '</td>' .
			'</tr></form></tbody>';
	}

	$isTasksPage = $_REQUEST['page'] == 'tasks';

	startPortlet ('Tasks Frequency');

	echo '<table cellspacing=0 cellpadding=5 align=center '
		. 'class="tablesorter widetable" name=tasksfrequencytable id=tasksfrequencytable>';
	echo '<thead><tr><th data-sorter="false" data-filter="false" class="filter-false">&nbsp;</th>';

	echo '<th>name</th>';

	if (getConfigVar ('TASKS_HIDE_ID') != 'yes') {
		echo '<td>id</td>';
	}

	echo	'<th>format</th>' .
		'<th>next due time</th>' .
		'<th data-sorter="false" data-filter="false" class="filter-false">&nbsp;</th>' .
		'</tr></thead>';

	if (getConfigVar ('ADDNEW_AT_TOP') == 'yes')
		printNewItemTR ();

	echo '<tbody>';
	$tasks = getTasksFrequencies ();
	foreach ($tasks as $task_id => $task)
	{
		renderTasksFrequency ($task['id'], false);
	}
	echo '</tbody>';

	if (getConfigVar ('ADDNEW_AT_TOP') != 'yes')
		printNewItemTR ();

	echo "</table>\n";
	echo '<div id="pager" class="pager"><form>
		<img src="?module=chrome&uri=tasks/images/first.png" class="first"/>
		<img src="?module=chrome&uri=tasks/images/prev.png" class="prev"/>
		<span class="pagedisplay" data-pager-output-filtered="{startRow:input} &ndash; {endRow} / {filteredRows} of {totalRows} total rows"></span>
		<!-- <input type="text" class="pagedisplay"/> -->
		<img src="?module=chrome&uri=tasks/images/next.png" class="next"/>
		<img src="?module=chrome&uri=tasks/images/last.png" class="last"/>
		<select class="pagesize">
			<option value="5">5 per page</option>
			<option value="10">10 per page</option>
			<option value="20">20 per page</option>
			<option value="30">30 per page</option>
			<option value="40">40 per page</option>
			<option value="50">50 per page</option>
			<option value="all">all</option>
		</select>
		<select class="gotoPage" title="Select page number"></select>
		</form>
		</div>';
	finishPortlet ();
}

function renderTasksFrequency ($task_frequency_id = 0, $isVertical = true, $isTasksPage = true)
{
	global $page, $tab, $remote_username;

	renderTasksFrequenciesGlobals ();

	if (isset($_REQUEST['task_item_id'])) {
		$task_item_id = intval(genericAssertion ('task_item_id', 'uint'));
	}

	$isViewTab = empty($_REQUEST['tab']) || in_array($_REQUEST['tab'], array('default', 'history', 'frequencies'));
	$isAddTab  = !empty($_REQUEST['tab']) && $_REQUEST['tab'] == 'add';

	$task      = getTasksFrequencies ($task_frequency_id);

	$task      = reset($task);
	if (empty($task)) {
		$task = array('id' => $task_item_id, 'name' => 'missing', 'format' => 'mising');
	}

	if ($isVertical) {
		startPortlet ('Tasks Frequency');
		echo '<table cellspacing=0 cellpadding=5 align=center>';
	} else {
		echo '<tr><td>&nbsp;</td>';
	}

	if (!$isViewTab) {
		printOpFormIntro ('upd', array ('task_frequency_id' => $task['id']));
	}

	$now = new DateTime();

	$label = mkA (stringForTD ($task['name']), 'tasksfrequency', $task['id'], $isVertical?'edit':NULL);
	$input = "<input size=24 name=name value='" . htmlspecialchars($task['name']) . "'>";
	renderTasksEditField ($isViewTab, $isVertical, '', 'name', $label, $input);

	if (getConfigVar ('TASKS_HIDE_ID') != 'yes') {
		$label = $task_frequency_id;
		renderTasksEditField ($isViewTab, $isVertical, '', 'id', $label, $label);
	}

	$label = $task['format'];
	$input = "<textarea cols=48 rows=4 name=format>" . htmlspecialchars($task['format']) . "</textarea>";
	renderTasksEditField ($isViewTab, $isVertical, '', 'format', $label, $input);

	$label = getTasksNextDue($task['format'], $now, false);
	renderTasksEditField ($isViewTab, $isVertical, '', 'example due', $label, $label);

	$isComplete = false;
	$isEditable = !($isComplete || $isViewTab);

	if (isTasksDebugUser()) {
		echo $task['id'] . ' : ';
		echo $isComplete ? "COMPLETED" : "INCOMPLETE";
		echo " ";
		echo $isViewTab ? "VIEW" : "EDIT";
		echo " ";
		echo $isVertical ? "VERTICAL" : "HORIZONTAL";
		echo " ";
		echo $isEditable ? "EDITABLE" : "READONLY";
		echo "\n";
	}

	$label = '&nbsp;';
	$input = getImageHREF ('save', 'update this task', TRUE);
	renderTasksEditField ($isViewTab || !$isEditable, $isVertical, '', '', $label, $input);

	if ($isVertical) {
		echo "</table></form>\n";
		finishPortlet ();
	} else {
		echo "</tr>";
	}
}
