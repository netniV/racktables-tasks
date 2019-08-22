<?php

function renderTasksDefinitionsGlobals () {
	static $isRenderedTasksDefinitionsGlobal = false;

	if (!$isRenderedTasksDefinitionsGlobal) {
		$isRenderedTasksDefinitionsGlobal = true;

		renderJSLinks();

		echo getTasksFrequencyFormatSuggestionList ();
	}
}

function renderTasksDefinitions ()
{
	renderTasksDefinitionsGlobals ();

	function printNewItemTR ()
	{
		echo '<tbody class="newrow">';
		printOpFormIntro ('add');
		echo '<tr>' .
			'<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>';

		if (getConfigVar('TASKS_HIDE_ID') != 'yes') {
			echo '<td>&nbsp;</td>';
		}

		echo	'<td><input type=text size=24 name=name></td>' .
			'<td><input type=text size=48 name=description></td>' .
			'<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'enabled'), 'yes') . '</td>' .
			'<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'repeat'), 'yes') . '</td>' .
			'<td>' . getSelect (getTasksModes(), array ('name' => 'type'), 'due') . '</td>' .
			'<td><input type=text size=24 name=start_time class="tasks-datetime"></td>' .
			'<td>' . getSelect (getTasksFrequencyEntities(), array('name' => 'frequency_id'), 0, FALSE) . '</td>' .
			'<td>' . getSelect (getTasksObjectEntities(), array('name' => 'object_id'), 0, FALSE) . '</td>' .
			'<td>&nbsp;<input type=hidden name=details value=""></td>' .
			'<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>' .
			'</tr></form></tbody>';
	}

	startPortlet ('Task Definitions');

	echo '<table cellspacing=0 cellpadding=5 align=center class="tablesorter widetable" name=tasksdefinitiontable id=tasksdefinitiontable>';
	echo '<thead><tr>' .
		'<th data-sorter="false" data-filter="false" class="filter-false">&nbsp;</th>';

	if (getConfigVar ('TASKS_HIDE_ID') != 'yes') {
		echo '<th>id</th>';
	}

	echo	'<th>task</th>' .
		'<th>definition</th>' .
		'<th>enabled</th>' .
		'<th>repeat</th>' .
		'<th>type</th>' .
		'<th>start_time</th>' .
		'<th>frequency</th>' .
		'<th>object</th>' .
		'<th>item(s)</th>' .
		'<th data-sorter="false" data-filter="false" class="filter-false">&nbsp;</th>' .
		'</tr></thead>';

	if (getConfigVar ('ADDNEW_AT_TOP') == 'yes')
		printNewItemTR ();

	echo '<tbody>';
	if (!isset($_REQUEST['tab']) || $_REQUEST['tab'] != 'add') {
		foreach (getTasksDefinitions () as $definition)
		{
			renderTasksDefinition ($definition['id'], false);
		}
	}
	echo '</tbody>';

	if (getConfigVar ('ADDNEW_AT_TOP') != 'yes')
		printNewItemTR ();

	echo '</table>';
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
}

function renderTasksDefinition ($tasks_definition_id = 0, $isVertical = true)
{
	global $remote_username;

	renderTasksDefinitionsGlobals ();

	if (isset($_REQUEST['tasks_definition_id'])) {
		$tasks_definition_id = intval(genericAssertion ('tasks_definition_id', 'uint'));
	}

	$isViewTab = empty($_REQUEST['tab']) || $_REQUEST['tab'] == 'default' || $_REQUEST['tab'] == 'definitions';
	$isAddTab  = !empty($_REQUEST['tab']) && $_REQUEST['tab'] == 'add';

	$definition = getTasksDefinitions ($tasks_definition_id);
	$definition = reset($definition);
	if (empty($definition)) {
		$definition = array('id' => $tasks_definition_id, 'name' => 'missing', 'description' => 'mising',
			'object_id' => '0', 'object_name' => 'missing',
			'frequency_id' => '0', 'frequency_name' => 'missing');
	}

	$object_id = $definition['object_id'];

	$isComplete = false;
	$isEditable = (!$isComplete && !$isViewTab);
	if (isTasksDebugUser()) {
		echo $tasks_definition_id . ' : ';
		echo $isComplete ? "COMPLETED" : "INCOMPLETE";
		echo " ";
		echo $isViewTab ? "VIEW" : "EDIT";
		echo " ";
		echo $isVertical ? "VERTICAL" : "HORIZONTAL";
		echo " ";
		echo $isEditable ? "EDITABLE" : "READONLY";
		echo "\n";
	}

	if ($isVertical) {
		startPortlet ('Task Definition');
		if ($isEditable) {
			printOpFormIntro ('upd', array('task_definition_id' => $definition['id']));
			echo "<input type='hidden' name='task_definition_id' value='{$definition['id']}'>";
			echo "<input type='hidden' name='id' value='{$definition['id']}'>";
		}
		echo '<table cellspacing=0 cellpadding=5 align=center>';
	} else if ($tasks_definition_id == 0) {
		echo '<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>';
	} else if (!$isViewTab) {
		if ($definition['num_items'])
			printImageHREF ('nodestroy', 'cannot delete, tasks exist');
		else
			echo getOpLink (array ('op' => 'del', 'task_definition_id' => $definition['id']), '', 'destroy', 'delete this definition');
	} else {
		print '<td>&nbsp;</td>';
	}

	if (getConfigVar ('TASKS_HIDE_ID') != 'yes') {
		$label = $tasks_definition_id;
		renderTasksEditField ($isViewTab, $isVertical, '', 'id', $label, $label);
	}

	$label = mkA ( stringForTD ($definition['name']), 'tasksdefinition', $definition['id'], $isVertical?'edit':NULL);
	$input = '<input type=text size=24 name=name value="' . $definition['name'] . '">';
	renderTasksEditField ($isViewTab, $isVertical, '', 'task', $label, $input);

	$label = htmlspecialchars ($definition['description'], ENT_QUOTES, 'UTF-8');
	$input = '<input type=text size=48 name=description value="' . $definition['description'] . '">';
	renderTasksEditField ($isViewTab, $isVertical, '', 'definition', $label, $input);

	if ($isVertical) {
		$label = str_replace("\n",'<br/>', htmlspecialchars($definition['details'], ENT_QUOTES, 'UTF-8'));
		$input = '<textarea rows=5 cols=48 name=details>' . htmlspecialchars ($definition['details'], ENT_QUOTES, 'UTF-8') . '</textarea>';
		renderTasksEditField ($isViewTab, $isVertical, '', 'details', $label, $input);
	}

	$label = $definition['enabled'];
	$input = getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'enabled'), $definition['enabled']);
	renderTasksEditField ($isViewTab, $isVertical, '', 'enabled', $label, $input);

	$label = $definition['repeat'];
	$input = getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'repeat'), $definition['repeat']);
	renderTasksEditField ($isViewTab, $isVertical, '', 'repeat', $label, $input);

	$label = getTasksMode ($definition['mode']);
	$input = getSelect (getTasksModes(), array ('name' => 'mode'), $definition['mode']);
	renderTasksEditField ($isViewTab, $isVertical, '', 'mode', $label, $input);

	$label = getTasksDateTime($definition['start_time'], $isVertical);
	$input = "<input type=text size=24 name=start_time class='tasks-datetime' value='{$label}'>";
	renderTasksEditField ($isViewTab, $isVertical, '', 'start_time', $label, $input);

	$label = mkA ( stringForTD ($definition['frequency_name']), 'tasksfrequency', $definition['frequency_id']);
	$input = getSelect (getTasksFrequencyEntities (), array('name' => 'frequency_id'), $definition['frequency_id'], FALSE);
	renderTasksEditField ($isViewTab, $isVertical, '', 'frequency', $label, $input);

	$label = mkA ( stringForTD ($definition['object_name']), 'object', $definition['object_id']);
	$input = getSelect (getTasksObjectEntities (), array('name' => 'object_id'), $definition['object_id'], FALSE);
	renderTasksEditField ($isViewTab, $isVertical, '', 'object', $label, $input);

	$label = htmlspecialchars ($definition['num_items'], ENT_QUOTES, 'UTF-8');
	renderTasksEditField ($isViewTab, $isVertical, '', 'tasks', $label, $label);

	$label = '&nbsp;';
	if ($tasks_definition_id == 0) {
		$input = getImageHREF ('create', 'add a new definition', TRUE);
	} else {
		$input = getImageHREF ('save', 'update this definition', TRUE);
	}
	renderTasksEditField ($isViewTab, $isVertical, '', '', $label, $input);

	if ($isVertical) {
		echo '</tr></form></table>';
		finishPortlet ();
	} else {
		echo '</tr>';
	}

	if ($isViewTab && $isVertical) {
		renderTasksItems (NULL, $tasks_definition_id);
	}
}
