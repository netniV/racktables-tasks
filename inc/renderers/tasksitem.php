<?php

function renderTasksItemsGlobals () {
	static $isRendered = false;

	if (!$isRendered) {
		$isRendered = true;
		echo "<style>
	.pastdue, .overdue, .late {
		color: white;
	}

	.pastdue a, .overdue a, .late a {
		color: white;
	}

	.pastdue {
		background: yellow;
	}

	.overdue {
		background: orange;
	}

	.late {
		background: red;
	}
</style>";

	}
}

function renderTasksItems ($object_id)
{
	renderTasksItemsGlobals ();

	if (!isset($object_id)) {
		if (isset($_REQUEST['object_id'])) {
			$object_id = genericAssertion('object_id', 'uint');
		} else {
			$object_id = 0;
		}
	}

	$isTasksPage = $_REQUEST['page'] == 'tasks';
	if ($isTasksPage) {
		$isHistoryTab = isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'history';
	} else {
		$isHistoryTab = empty($_REQUEST['tab']) || $_REQUEST['tab'] == 'default';
	}

	$tasks = getTasksItems ($object_id, $isHistoryTab);
	$show  = true;
	if (($tasks === false || !count($tasks)) && (empty($_REQUEST['tab']) || $_REQUEST['tab'] == 'default')) {
		$show = false;
	}

	if ($show) {
		if ($isHistoryTab) {
			startPortlet ('Tasks History');
		} else {
			startPortlet ('Tasks Outstanding');
		}

		echo '<table cellspacing=0 cellpadding=5 align=center class=widetable>';
		echo '<tr><th>&nbsp;</th>';

		if ($isTasksPage) {
			echo '<th>object</th>';
		}

		echo '<th>task</th>' .
			'<th>definition</th>' .
			'<th>mode</th>' .
			'<th>created/due time</th>' .
			'<th>completed</th>' .
			'<th>completed time</th>' .
			'<th>completed user</th>' .
			'<th>notes</th>' .
			'<th>&nbsp;</th>' .
			'</tr>';

		$now = new DateTime();
		foreach ($tasks as $task_id => $task)
		{
			renderTasksItem ($task_id, false, $isTasksPage);
		}
		echo "</table>\n";
		finishPortlet ();
	}
}

function triggerTasksItems ()
{
/*	if (! count (getTasksDefinitions ()))
		return '';
	if
	(
		count (getTasksItems (getBypassValue (), true)) //or
		considerConfiguredConstraint (spotEntity ('object', getBypassValue ()), 'TASKS_LISTSRC')
	)
*/		return 'std';
	return '';
}

function renderTasksItem ($task_item_id = 0, $isVertical = true, $isTasksPage = true)
{
	global $page, $tab, $remote_username;

	renderTasksItemsGlobals ();

	if (isset($_REQUEST['task_item_id'])) {
		$task_item_id = intval(genericAssertion ('task_item_id', 'uint'));
	}

	$object_id = 0;
	if (isset($_REQUEST['object_id'])) {
		$object_id = intval(genericAssertion ('object_id', 'uint'));
	}

	$isViewTab = empty($_REQUEST['tab']) || $_REQUEST['tab'] == 'default' || $_REQUEST['tab'] == 'history';
	$isAddTab  = !empty($_REQUEST['tab']) && $_REQUEST['tab'] == 'add';

	$task      = getTasksItems ($object_id, $isViewTab, $task_item_id);
//	if (empty($task)) {
//		throw new EntityNotFoundException('TasksItem', $id);
//	}

	$task      = reset($task);
	if (empty($task)) {
		$task = array('id' => $task_item_id, 'name' => 'missing', 'description' => 'mising',
			'object_id' => '0', 'object_name' => 'missing',
			'frequency_id' => '0', 'frequency_name' => 'missing',
			'definition_id' => '0');
	}
	$object_id = $task['object_id'];

	$page['tasksitem']['parent'] = $object_id ? 'object:tasks' : 'tasks';

	if ($isVertical) {
		startPortlet ('Tasks Item');
		echo '<table cellspacing=0 cellpadding=5 align=center>';
	} else {
		echo '<td>&nbsp;</td>';
	}

	if (!$isViewTab) {
		printOpFormIntro ('upd', array ('id' => $task['id']));
	}

	$now = new DateTime();
	$color = 'transparent';
	$incomplete = 'incomplete';

	if ($task['completed'] == 'no' && $task['mode'] == 'due') {
		$created = new DateTime($task['created_time']);
		$diff  = $now->diff($created);
		$incomplete = getTasksDiffString($diff);

		if ($created <= $now) {
			$freq  = $task['frequency_format'];
			$next  = clone $created;
			$count = 0;

			while ($count < 3 && $next < $now) {
				$next = getTasksNextDue($freq, $next);
				$count++;
			}

			if ($count > 2) {
				$color = 'late';
			} else if ($count > 1) {
				$color = 'overdue';
			} else {
				$color = 'pastdue';
			}
		}
	}

//			echo '<tr style="background: ' . $color . ';"><td>';
	if ($object_id > 0 & $isTasksPage) {
		$label = (empty($task['object_name'])) ? '' : mkA (stringForLabel ($task['object_name']), 'object', $task['object_id']);
		$input = getSelect (getTasksObjectEntities(), array('name' => 'object_id', 'id' => 'id'), $task['object_id'], FALSE);
		renderTasksEditField (!$isAddTab, $isVertical, 'object', $label, $input);
	}

	$label = mkA (stringForLabel ($task['name']), 'tasksitem', $task['id'], $isVertical?'edit':NULL);
	$input = stringForLabel ($task['name']);
	renderTasksEditField ($isViewTab, $isVertical, 'task', $label, $input);

	$label = mkA (stringForLabel ($task['description']), 'tasksdefinition', $task['definition_id']);
	renderTasksEditField ($isViewTab, $isVertical, 'definition', $label, $label);

	$label = htmlspecialchars ($task['mode'], ENT_QUOTES, 'UTF-8');
	renderTasksEditField ($isViewTab, $isVertical, 'mode', $label, $label);

	$label = htmlspecialchars ($task['created_time'], ENT_QUOTES, 'UTF-8');
	renderTasksEditField ($isViewTab, $isVertical, 'created', $label, $label);

	$isComplete = $task['completed'] == 'yes';
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

	$label = htmlspecialchars ($task['completed'], ENT_QUOTES, 'UTF-8');
	$input = getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'completed', 'id' => 'completed'), $task['completed']);
	renderTasksEditField ($isViewTab, $isVertical, 'completed', $label, $isEditable ? $input : $label);

	if (!$isComplete) {
		$label = mkA (stringForLabel ($task['frequency_name']), 'tasksfrequency', $task['frequency_id']) . ' ' . $incomplete;
		renderTasksEditField ($isViewTab, $isVertical, 'frequency', $label, $label, 2, $color);
	} else {
		$label = htmlspecialchars ($task['completed_time'], ENT_QUOTES, 'UTF-8');
		renderTasksEditField ($isViewTab, $isVertical, 'completed', $label, $label);

		$label = htmlspecialchars ($task['completed_by'], ENT_QUOTES, 'UTF-8');
		renderTasksEditField ($isViewTab, $isVertical, 'completed by', $label, $label);
	}

	$label = htmlspecialchars ($task['notes'], ENT_QUOTES, 'UTF-8');
	$input = '<input type=textarea size=48 name=notes value="' . $label . '">';
	renderTasksEditField ($isViewTab || !$isEditable, $isVertical, 'notes', $label, $input);

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