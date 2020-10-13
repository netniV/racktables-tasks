<?php

function renderTasksItemsGlobals () {
	static $isRenderedTasksItemsGlobal = false;

	if (!$isRenderedTasksItemsGlobal) {
		$isRenderedTasksItemsGlobal = true;

		renderJSLinks();
	}
}

function renderTasksItems ($object_id = NULL, $task_definition_id = NULL)
{
	renderTasksItemsGlobals ();

	$_PAGE = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
	$_TAB  = isset($_REQUEST['tab'])  ? $_REQUEST['tab'] : '';

	if ($object_id == NULL) {
		if (isset($_REQUEST['object_id'])) {
			$object_id = genericAssertion('object_id', 'uint');
		} else {
			$object_id = 0;
		}
	}

	if ($task_definition_id == NULL) {
		if (isset($_REQUEST['task_definition_id'])) {
			$task_definition_id = genericAssertion('task_definition_id', 'uint');
		} else {
			$task_definition_id = 0;
		}
	}

	$isTasksPage = $_PAGE == 'tasks';
	$isDefinitionPage = $_PAGE == 'tasksdefinition';

	if ($isTasksPage) {
		$isHistoryTab = $_TAB == 'history';
		$title = $isHistoryTab ? 'Tasks History' : 'Tasks Outstanding';
	} else if ($isDefinitionPage) {
		$isHistoryTab = empty($_TAB) || $_TAB == 'default';
		$title = 'Tasks';
	} else {
		$isHistoryTab = $_TAB == 'tasksitem';
		$title = $isHistoryTab ? 'Tasks' : 'Tasks Outstanding';
	}

	$isAddTab = $_TAB == 'add';
	$tasks = array();
	if (!$isHistoryTab || ($isHistoryTab && !$isTasksPage)) {
		$temp  = getTasksItems ($object_id, 'no', 0, $task_definition_id);
		if ($temp !== false && sizeof($temp)) {
			$tasks = array_merge($tasks, $temp);
		}
	}

	if ($isHistoryTab) {
		$temp  = getTasksItems ($object_id, 'yes', 0, $task_definition_id);
		if ($temp !== false && sizeof($temp)) {
			$tasks = array_merge($tasks, $temp);
		}
	}

	$show  = true;
	if (($tasks === false || !count($tasks)) && (empty($_TAB) || $_TAB == 'default')) {
		$show = false;
	}

	if ($show) {
		if ($isAddTab) {
			renderTasksItem(0);
			return;
		}

		startPortlet ($title);

		$tableName = ($isTasksPage || $isHistoryTab) ? 'taskstable' : 'tasksitemtable';
		echo '<table cellspacing=0 cellpadding=5 align=center class="tablesorter widetable" id=' . $tableName . ' name=' . $tableName . '>';
		echo '<thead><tr><th data-sorter="false" data-filter="false" class="filter-false">&nbsp;</th>';

		if (getConfigVar ('TASKS_HIDE_ID') != 'yes') {
			echo '<th>id</th>';
		}

		echo '<th>task</th>';
		echo '<th>department</th>';
		echo '<th>definition</th>';
		if ($isTasksPage) {
			echo '<th>object</th>';
		}

		if (getConfigVar ('TASKS_HIDE_MODE') != 'yes') {
			echo '<th>type</th>';
		}

		echo '<th>date</th>';

		if (!$isTasksPage) {
			echo	'<th>completed</th>';
		}

		if (!$isTasksPage || $isHistoryTab) {
			echo '<th>due or completed time</th>';
		}

		if ($isHistoryTab) {
			echo	'<th>user</th>';
		}

		if (!$isTasksPage || $isHistoryTab) {
			echo	'<th>notes</th>';
		}

		echo '<th data-sorter="false" data-filter="false" class="filter-false">&nbsp;</th>';
		echo '</tr></thead><tbody>';

		$now = new DateTime();
		foreach ($tasks as $task_id => $task)
		{
			renderTasksItem ($task['id'], false, $isTasksPage, $isHistoryTab);
		}

		echo "</tbody></table>\n";
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

function renderTasksItem ($task_item_id = 0, $isVertical = true, $isTasksPage = false, $isHistoryTab = false)
{
	global $remote_username;

	$_PAGE = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
	$_TAB  = isset($_REQUEST['tab'])  ? $_REQUEST['tab'] : '';

	if (isTasksDebugUser()) {
		echo "renderTasksItem (id: $task_item_id, isVertical: $isVertical, isTasksPage: $isTasksPage, isHistoryTab: $isHistoryTab)<br/>";
	}

	global $page, $tab, $remote_username;

	renderTasksItemsGlobals ();

	if (isset($_REQUEST['task_item_id'])) {
		$task_item_id = intval(genericAssertion ('task_item_id', 'uint'));
	}

	$object_id = 0;
	if (isset($_REQUEST['object_id'])) {
		$object_id = intval(genericAssertion ('object_id', 'uint'));
	}

	$isViewTab = empty($_TAB) || $_TAB == 'default' || $_TAB == 'history';
	$isAddTab  = $_TAB == 'add';

	$task      = getTasksItems ($object_id, NULL, $task_item_id);
//	if (empty($task)) {
//		throw new EntityNotFoundException('TasksItem', $id);
//	}

	$task      = reset($task);
	if (empty($task)) {
		$task = array('id' => $task_item_id, 'name' => 'missing',
			'department' => 'missing', 'description' => 'mising',
			'object_id' => '0', 'object_name' => 'missing',
			'frequency_id' => '0', 'frequency_name' => 'missing',
			'definition_id' => '0', 'completed' => 'missing');
	}
	$object_id = $task['object_id'];

	$page['tasksitem']['parent'] = $object_id ? 'object:tasks' : 'tasks';

	$now = new DateTime();
	$color = 'transparent';
	$incomplete = '';

	if ($task['completed'] == 'no' && $task['mode'] != 'schedule') {
		$created = new DateTime($task['created_time']);
		$diff  = $now->diff($created);

		if ($created <= $now || $isVertical) {
			$incomplete = getTasksDiffString($diff);
		}

		if ($created <= $now) {
			$freq  = $task['frequency_format'];
			$next  = clone $created;
			$count = 0;

			while ($count < 3 && $next < $now) {
				$next = getTasksNextDue($freq, $next);
				$count++;
			}

			$total_days = $next->diff($created)->format("%a");

			if ($count > 2 || $total_days > 30) {
				$color = 'late';
			} else if ($count > 1) {
				$color = 'overdue';
			} else {
				$color = 'pastdue';
			}
		} else {
			if ($diff->days <= 6) {
				$color = 'due';
			}
		}
	}

	if ($isVertical) {
		startPortlet ('Tasks Item');
		echo '<table cellspacing=0 cellpadding=5 align=center>';
	} else {
		echo "<tr class='$color'>";
		echo '<td>&nbsp;</td>';
	}

	if (!$isViewTab) {
		printOpFormIntro ('upd', array ('task_item_id' => $task['id']));
	}

	$prefix = 'task_' . $task['id'] . '_';

	$label = "<input type='hidden' name='id' value='{$task['id']}'>";
	if (getConfigVar ('TASKS_HIDE_ID') != 'yes') {
		$label = $task_item_id . $label;
		renderTasksEditField ($isViewTab, $isVertical, $prefix . 'id', 'id', $label, $label);
	}

	$label = mkA (stringForTD ($task['name']), 'tasksitem', $task['id'], $isVertical?'edit':NULL);
	$input = stringForTD ($task['name']);
	renderTasksEditField ($isViewTab || !$isVertical, $isVertical, $prefix . 'name', 'task', $label, $input);

	$input = stringForTD ($task['department']);
	renderTasksEditField ($isViewTab, $isVertical, $prefix . 'definition', 'definition', $input, $input);

	if (empty($task['description'])) {
		$tasks['description'] = 'definition ' + $task['definition_id'];
	}

	$label = mkA (stringForTD ($task['description'], 75), 'tasksdefinition', $task['definition_id']);
	renderTasksEditField ($isViewTab, $isVertical, $prefix . 'definition', 'definition', $label, $label);

	if ($isTasksPage) {
		if ($object_id) {
			$label = (empty($task['object_name'])) ? '' : mkA (stringForTD ($task['object_name']), 'object', $task['object_id']);
			$input = getSelect (getTasksObjectEntities(), array('name' => 'object_id', 'id' => 'id'), $task['object_id'], FALSE);
		} else {
			$label = stringForTD('');
			$input = $label;
		}
		renderTasksEditField (!$isAddTab, $isVertical, $prefix . 'object', 'object', $label, $input);
	}

	if ($isVertical && $isViewTab) {
		$label = str_replace("\n",'<br/>', htmlspecialchars ($task['details'], ENT_QUOTES, 'UTF-8'));
		renderTasksEditField ($isViewTab, $isVertical, '', 'details', $label, $label);
	}

	if (getConfigVar ('TASKS_HIDE_MODE') != 'yes') {
		$label = htmlspecialchars (getTasksMode ($task['mode']), ENT_QUOTES, 'UTF-8');
		renderTasksEditField ($isViewTab, $isVertical, $prefix . 'mode', 'mode', $label, $label);
	}

	$label = htmlspecialchars (getTasksDateTime ($task['created_time'], $isVertical), ENT_QUOTES, 'UTF-8');
	renderTasksEditField ($isViewTab, $isVertical, $prefix . 'date', 'date', $label, $label);

	$isComplete = $task['completed'] == 'yes';
	$isEditable = !($isComplete || $isViewTab);

	if (isTasksDebugUser()) {
		echo $task['id'] . ' : ';
		echo $isTasksPage ? "TasksPage" : "";
		echo " ";
		echo $isComplete ? "Complete" : "";
		echo " ";
		echo $isViewTab ? "View" : "";
		echo " ";
		echo $isVertical ? "Vertical" : "";
		echo " ";
		echo $isEditable ? "Editable" : "";
		echo "<br>\n";
	}

	if (!$isTasksPage) {
		$label = htmlspecialchars ($task['completed'], ENT_QUOTES, 'UTF-8');
		$input = getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'completed', 'id' => 'completed'), $task['completed']);
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, $prefix . 'completed', 'completed', $label, $isEditable ? $input : $label);
	}

	if ($isComplete) {
		$incomplete = getTasksDateTime($task['completed_time'], $isVertical);
	}

	if (!$isTasksPage || $isHistoryTab) {
		$label = $incomplete;
		$input = '<input type=text name=completed_time class="tasks-datetime" value="' . $task['completed_time'] . '">';
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, $prefix . 'completed_time', 'when', $label, $isEditable ? $input : $label, 1, $color);
	}

	if ($isHistoryTab || $isVertical) {
		$label = htmlspecialchars ($task['completed_by'], ENT_QUOTES, 'UTF-8');
		$input = '<input type=text name=completed_by value="' . $task['completed_by'] . '">';
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, $prefix . 'completed_by', 'who', $label, $isEditable ? $input : $label);
	}

	if (!$isTasksPage || $isHistoryTab) {
		$label = htmlspecialchars ($task['notes'], ENT_QUOTES, 'UTF-8');
		$input = '<textarea rows=4 cols=48 name=notes>' . $label . '</textarea>';
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, $prefix . 'notes', 'notes', $label, $isEditable ? $input : $label);
	}

	if (!$isVertical && !$isComplete) {
		$label = '<i id="task_complete_' . $task['id'] . '" class="far fa-circle"></i>'
			. '<div id="' . $prefix . 'dialog" style="display:none"><table width="100%">'
			. '<tr><td><b>Who:</b></td><td><input type=text name=completed_by value="' . $task['completed_by'] . '"></td>'
			. '<td><b>When:</b></td><td><input type=text name=completed_time value="' . $task['completed_time'] . '"></td></tr>'
			. '<tr><td colspan="4"><b>Notes:</b></td></tr>'
			. '<tr><td colspan="4"><textarea rows=8 cols=60 name=notes>' . $task['notes'] . '</textarea></td></tr>'
			. '</table></div>';
	} else {
		$label = '&nbsp;';
	}
	$input = getImageHREF ('save', 'update this task', TRUE);
	renderTasksEditField ($isViewTab || $isHistoryTab || !$isEditable, $isVertical, '', '', $label, $input);

	if ($isVertical) {
		echo "</table></form>\n";
		finishPortlet ();
	} else {
		echo "</tr>";
	}
}
