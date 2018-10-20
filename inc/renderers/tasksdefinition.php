<?php

function renderTasksDefinitionGlobals () {
	static $isRendered = false;

	if (!$isRendered) {
		$isRendered = true;
		echo "<script>$(function() {
			$.getScript('https://cdn.jsdelivr.net/npm/flatpickr', function() {
				$('.tasks-datetime').flatpickr({
					enableTime: true,
					dateFormat: 'Y-m-d H:i:S',
					altInput: true,
					altFormat: 'M j, Y H:i:S',
					time_24hr: true,
				});
			});
		});</script>";

		echo getTasksFrequencyFormatSuggestionList ();
	}
}

function renderTasksDefinitions ()
{
	renderTasksDefinitionGlobals ();

	function printNewItemTR ()
	{
		printOpFormIntro ('add');
		echo '<tr>' .
			'<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>' .
			'<td><input type=text size=24 name=name></td>' .
			'<td><input type=text size=48 name=description></td>' .
			'<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'enabled', 'id' => 'enabled'), 'yes') . '</td>' .
			'<td>' . getSelect (getTasksModes(), array ('name' => 'mode', 'id' => 'mode'), 'due') . '</td>' .
			'<td><input type=text size=24 name=start_time class="tasks-datetime"></td>' .
			'<td>' . getSelect (getTasksFrequencyEntities(), array('name' => 'frequency_id', 'id' => 'id'), 0, FALSE) . '</td>' .
			'<td>' . getSelect (getTasksObjectEntities(), array('name' => 'object_id', 'id' => 'id'), 0, FALSE) . '</td>' .
			'<td>&nbsp;</td>' .
			'<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>' .
			'</tr></form>';
	}

	echo '<table cellspacing=0 cellpadding=5 align=center class=widetable>';
	echo '<tr>' .
		'<th>&nbsp;</th>' .
		'<th>name</th>' .
		'<th>description</th>' .
		'<th>enabled</th>' .
		'<th>mode</th>' .
		'<th>start_time</th>' .
		'<th>frequency</th>' .
		'<th>object</th>' .
		'<th>item(s)</th>' .
		'<th>&nbsp;</th>' .
		'</tr>';

	if (getConfigVar ('ADDNEW_AT_TOP') == 'yes')
		printNewItemTR ();

	foreach (getTasksDefinitions () as $definition)
	{
		renderTasksDefinition ($definition['id'], false);
	}

	if (getConfigVar ('ADDNEW_AT_TOP') != 'yes')
		printNewItemTR ();

	echo '</table>';
}

function renderTasksDefinition ($tasks_definition_id = 0, $isVertical = true)
{
	renderTasksDefinitionGlobals ();

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

	if ($isVertical) {
		startPortlet ('Task Definition');
		echo '<table cellspacing=0 cellpadding=5 align=center>';
	} else if ($tasks_definition_id == 0) {
		echo '<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>';
	} else if (!$isViewTab) {
		if ($definition['num_items'])
			printImageHREF ('nodestroy', 'cannot delete, tasks exist');
		else
			echo getOpLink (array ('op' => 'del', 'id' => $definition['id']), '', 'destroy', 'delete this definition');
	} else {
		print '<td>&nbsp;</td>';
	}

	$isComplete = false;
	$isEditable = (!$isComplete && !$isViewTab);
	echo $isComplete ? "COMPLETED" : "INCOMPLETE";
	echo " ";
	echo $isViewTab ? "VIEW" : "EDIT";
	echo " ";
	echo $isVertical ? "VERTICAL" : "HORIZONTAL";
	echo " ";
	echo $isEditable ? "EDITABLE" : "READONLY";

	$label = mkA ( stringForLabel ($definition['name']), 'tasksdefinition', $definition['id'], $isVertical?'edit':NULL);
	$input = '<input type=text size=24 name=name value="' . stringForLabel ($definition['name']) . '">';
	renderTasksEditField ($isViewTab, $isVertical, 'name', $label, $input);

	$label = htmlspecialchars ($definition['description'], ENT_QUOTES, 'UTF-8');
	$input = '<input type=text size=48 name=description value="' . $label . '">';
	renderTasksEditField ($isViewTab, $isVertical, 'description', $label, $input);

	$label = $definition['enabled'];
	$input = getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'enabled', 'id' => 'enabled'), $definition['enabled']);
	renderTasksEditField ($isViewTab, $isVertical, 'enabled', $label, $input);

	$label = $defintiion['mode'];
	$input = getSelect (getTasksModes(), array ('name' => 'mode', 'id' => 'mode'), $definition['mode']);
	renderTasksEditField ($isViewTab, $isVertical, 'mode', $label, $input);

	$label = $definition['start_time'];
	$input = "<input type=text size=24 name=start_time class='tasks-datetime' value='{$label}'>";
	renderTasksEditField ($isViewTab, $isVertical, 'start_time', $label, $input);

	$label = htmlspecialchars ($definition['frequency_name'], ENT_QUOTES, 'UTF-8');
	$input = getSelect (getTasksFrequencyEntities (), array('name' => 'frequency_id', 'id' => 'id'), $defintiion['frequency_id'], FALSE);
	renderTasksEditField ($isViewTab, $isVertical, 'frequency', $label, $input);

	$label = htmlspecialchars ($definition['object_name'], ENT_QUOTES, 'UTF-8');
	$input = getSelect (getTasksObjectEntities (), array('name' => 'name', 'id' => 'object_id'), $definition['object_id'], FALSE);
	renderTasksEditField ($isViewTab, $isVertical, 'object', $label, $input);

	$label = htmlspecialchars ($definition['num_items'], ENT_QUOTES, 'UTF-8');
	renderTasksEditField ($isViewTab, $isVertical, 'tasks', $label, $label);

	$label = '&nbsp;';
	if ($definition_id == 0) {
		$input = getImageHREF ('create', 'add a new definition', TRUE);
	} else {
		$input = getImageHREF ('save', 'update this definition', TRUE);
	}
	renderTasksEditField ($isViewTab, $isVertical, '', $label, $input);

	if ($isVertical) {
		echo '</tr></form>';
		finishPortlet ();
	}
}
