<?php

function renderTasksFrequencies ($object_id)
{
	function printNewItemTR ()
	{
		printOpFormIntro ('add');
		echo '<tr>' .
			'<td>' . getImageHREF ('create', 'add a new frequency', TRUE) . '</td>' .
			'<td><input type=text size=24 name=name></td>' .
			'<td><input type=text size=48 name=format></td>' .
			'<td>&nbsp;</td>' .
			'<td>' . getImageHREF ('create', 'add a new frequency', TRUE) . '</td>' .
			'</tr></form>';
	}

	$isTasksPage = $_REQUEST['page'] == 'tasks';

	startPortlet ('Tasks Frequency');

	echo '<table cellspacing=0 cellpadding=5 align=center class=widetable>';
	echo '<tr><th>&nbsp;</th>';

	echo '<th>name</th>' .
		'<th>format</th>' .
		'<th>next due time</th>' .
		'<th>&nbsp;</th>' .
		'</tr>';

	if (getConfigVar ('ADDNEW_AT_TOP') == 'yes')
		printNewItemTR ();

	$tasks = getTasksFrequencies ();
	foreach ($tasks as $task_id => $task)
	{
		renderTasksFrequency ($task['id'], false);
	}

	if (getConfigVar ('ADDNEW_AT_TOP') != 'yes')
		printNewItemTR ();


	echo "</table>\n";
	finishPortlet ();
}

function renderTasksFrequency ($task_frequency_id = 0, $isVertical = true, $isTasksPage = true)
{
	global $page, $tab, $remote_username;

	if (isset($_REQUEST['task_frequency_id'])) {
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
		printOpFormIntro ('upd', array ('id' => $task['id']));
	}

	$now = new DateTime();

	$label = mkA (stringForLabel ($task['name']), 'tasksfrequency', $task['id'], $isVertical?'edit':NULL);
	$input = "<input size=24 name=name value='" . htmlspecialchars($task['name']) . "'>";
	renderTasksEditField ($isViewTab, $isVertical, 'name', $label, $input);

	$label = $task['format'];
	$input = "<textarea cols=48 rows=4 name=format>" . htmlspecialchars($task['format']) . "</textarea>";
	renderTasksEditField ($isViewTab, $isVertical, 'format', $label, $input);

	$label = getTasksNextDue($task['format'], $now, false);
	renderTasksEditField ($isViewTab, $isVertical, 'example due', $label, $label);

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
	renderTasksEditField ($isViewTab || !$isEditable, $isVertical, '', $label, $input);

	if ($isVertical) {
		echo "</table></form>\n";
		finishPortlet ();
	} else {
		echo "</tr>";
	}
}
