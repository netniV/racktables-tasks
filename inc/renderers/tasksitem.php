<?php

function renderTasksItemsGlobals () {
	static $isRenderedTasksItemsGlobal = false;

	if (!$isRenderedTasksItemsGlobal) {
		$isRenderedTasksItemsGlobal = true;

		renderJSLinks();

		global $remote_username;
		echo <<<ENDOFSCRIPT
<script>
$(function() {
	$('#completed').change(function() {
		var getZeroPrefix = function(d) {
			return (d < 10 ? '0' : '') + d;
		}

		var completed = $('#completed').val() == 'yes';

		if (completed) {
 			var d = new Date();
			var c = d.getFullYear() + '-' + getZeroPrefix(d.getMonth()) + '-' + getZeroPrefix(d.getDate()) + ' ' +
				getZeroPrefix(d.getHours()) + ':' + getZeroPrefix(d.getMinutes()) + ':' + getZeroPrefix(d.getSeconds());

			var u = '{$remote_username}';
		} else {
			let c = '';
			let u = '';
		}
		$('input[name=completed_time]').val(c);
		$('input[name=completed_by]').val(u);
	});
	makeTableSortable('#taskstable');
});
</script>
ENDOFSCRIPT;
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
		$title = $isHistoryTab ? 'Tasks History' : 'Tasks Outstanding';
	}

	$isAddTab = $_TAB == 'add';
	$tasks = array();
	if (!$isTasksPage || ($isTasksPage && !$isHistoryTab)) {
		$temp  = getTasksItems ($object_id, 'no', 0, $task_definition_id);
		if ($temp !== false && sizeof($temp)) {
			$tasks = array_merge($tasks, $temp);
		}
	}

	if (!$isTasksPage || ($isTasksPage && $isHistoryTab)) {
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

		echo '<table cellspacing=0 cellpadding=5 align=center class="tablesorter widetable" id=taskstable name=taskstable>';
		echo '<thead><tr><th data-sorter="false" data-filter="false" class="filter-false">&nbsp;</th>';

		echo '<th>task</th>';
		echo '<th>definition</th>';
		if ($isTasksPage) {
			echo '<th>object</th>';
		}
		echo '<th>mode</th>';

		echo '<th>date</th>';

		if ($isHistoryTab) {
			echo	'<th>completed</th>';
		}

		echo '<th>due or completed time</th>';

		if ($isHistoryTab) {
			echo	'<th>user</th>' .
				'<th>notes</th>' .
				'<th>&nbsp;</th>';
		}

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

function renderTasksItem ($task_item_id = 0, $isVertical = true, $isTasksPage = true, $isHistoryTab = false)
{
	global $remote_username;

	$_PAGE = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
	$_TAB  = isset($_REQUEST['tab'])  ? $_REQUEST['tab'] : '';

	if (isTasksDebugUser()) {
		echo "renderTasksItem (id: $task_item_id, isVertical: $isVertical, isTasksPage: $isTasksPage)<br/>";
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
		$task = array('id' => $task_item_id, 'name' => 'missing', 'description' => 'mising',
			'object_id' => '0', 'object_name' => 'missing',
			'frequency_id' => '0', 'frequency_name' => 'missing',
			'definition_id' => '0', 'completed' => 'missing');
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
		printOpFormIntro ('upd', array ('task_item_id' => $task['id']));
	}

	$now = new DateTime();
	$color = 'transparent';
	$incomplete = '';

	if ($task['completed'] == 'no' && $task['mode'] == 'due') {
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

			if ($count > 2) {
				$color = 'late';
			} else if ($count > 1) {
				$color = 'overdue';
			} else {
				$color = 'pastdue';
			}
		}
	}

	$label = mkA (stringForLabel ($task['name']), 'tasksitem', $task['id'], $isVertical?'edit':NULL);
	$input = stringForLabel ($task['name']);
	renderTasksEditField ($isViewTab || !$isVertical, $isVertical, 'task', $label, $input);

	if (empty($task['description'])) {
		$tasks['description'] = 'definition ' + $task['definition_id'];
	}

	$label = mkA (stringForLabel ($task['description'], 90), 'tasksdefinition', $task['definition_id']);
	renderTasksEditField ($isViewTab, $isVertical, 'definition', $label, $label);

	if ($isTasksPage) {
		if ($object_id) {
			$label = (empty($task['object_name'])) ? '' : mkA (stringForLabel ($task['object_name']), 'object', $task['object_id']);
			$input = getSelect (getTasksObjectEntities(), array('name' => 'object_id', 'id' => 'id'), $task['object_id'], FALSE);
		} else {
			$label = stringForLabel('');
			$input = $label;
		}
		renderTasksEditField (!$isAddTab, $isVertical, 'object', $label, $input);
	}

	$label = htmlspecialchars ($task['mode'], ENT_QUOTES, 'UTF-8');
	renderTasksEditField ($isViewTab, $isVertical, 'mode', $label, $label);

	$label = htmlspecialchars ($task['created_time'], ENT_QUOTES, 'UTF-8');
	renderTasksEditField ($isViewTab, $isVertical, 'date', $label, $label);

	$isComplete = $task['completed'] == 'yes';
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
		echo "<br>\n";
	}

	if ($isHistoryTab || $isVertical) {
		$label = htmlspecialchars ($task['completed'], ENT_QUOTES, 'UTF-8');
		$input = getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'completed', 'id' => 'completed'), $task['completed']);
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, 'completed', $label, $isEditable ? $input : $label);

		if ($isComplete) {
			$incomplete = $task['completed_time'];
		}

		$label = $incomplete;
		$input = '<input type=text name=completed_time value="' . $task['completed_time'] . '">';
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, 'completed', $label, $isEditable ? $input : $label, 1, $color);

		$label = htmlspecialchars ($task['completed_by'], ENT_QUOTES, 'UTF-8');
		$input = '<input type=text name=completed_by value="' . $task['completed_by'] . '">';
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, 'completed by', $label, $isEditable ? $input : $label);

		$label = htmlspecialchars ($task['notes'], ENT_QUOTES, 'UTF-8');
		$input = '<input type=textarea size=48 name=notes value="' . $label . '">';
		renderTasksEditField ($isViewTab || $isHistoryTab, $isVertical, 'notes', $label, $isEditable ? $input : $label);
	}

	$label = '&nbsp;';
	$input = getImageHREF ('save', 'update this task', TRUE);
	renderTasksEditField ($isViewTab || $isHistoryTab || !$isEditable, $isVertical, '', $label, $input);

	if ($isVertical) {
		echo "</table></form>\n";
		finishPortlet ();
	} else {
		echo "</tr>";
	}
}
