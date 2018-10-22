<?php

function renderTasksFrequencies ($object_id)
{
	$isTasksPage = $_REQUEST['page'] == 'tasks';

	$tasks = getTasksFrequencies ();
	$show  = true;
	if (($tasks === false || !count($tasks)) && (empty($_REQUEST['tab']) || $_REQUEST['tab'] == 'default')) {
		$show = false;
	}

	if ($show) {
		startPortlet ('Tasks Frequency');

		echo '<table cellspacing=0 cellpadding=5 align=center class=widetable>';
		echo '<tr><th>&nbsp;</th>';

		echo '<th>name</th>' .
			'<th>format<th>' .
			'<th>next due time</th>' .
			'<th>&nbsp;</th>' .
			'</tr>';

		foreach ($tasks as $task_id => $task)
		{
			renderTasksFrequency ($task_id, false);
		}
		echo "</table>\n";
		finishPortlet ();
	}
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
	$incomplete = 'incomplete';

	$label = mkA (stringForLabel ($task['name']), 'tasksfrequency', $task['id'], $isVertical?'edit':NULL);
	$input = "<input size=24 name=name value='" . htmlspecialchars($task['name']) . "'>";
	renderTasksEditField ($isViewTab, $isVertical, 'name', $label, $input);

	$label = stringForLabel ($task['format']);
	$input = "<input size=24 name=format value='" . htmlspecialchars($task['format']) . "'>";
	renderTasksEditField ($isViewTab, $isVertical, 'format', $label, $input);

	$isComplete = false;
	$isEditable = !($isComplete || $isViewTab);

	if ($remote_username == 'admin') {
		echo $isComplete ? "COMPLETED" : "INCOMPLETE";
		echo " ";
		echo $isViewTab ? "VIEW" : "EDIT";
		echo " ";
		echo $isVertical ? "VERTICAL" : "HORIZONTAL";
		echo " ";
		echo $isEditable ? "EDITABLE" : "READONLY";
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
