<?php

function initTasksNavigationTasksDefinition() {
	global $interface_requires, $opspec_list, $page, $tab, $trigger;

	$tab ['tasks']['definitions'] = 'Definitions';

	registerTabHandler ('tasks', 'definitions', 'renderTasksDefinitions');
	registerOpHandler  ('tasks', 'definitions', 'add', 'addTasksDefinition');

	/* Tasks Definitions in tasks */
	$page['tasksdefinitions']['title']       = 'Task Definitions';
	$page['tasksdefinitions']['parent']      = 'tasks:definitionstab';

	$tab ['tasksdefinitions']['default']     = 'Browse';
	$tab ['tasksdefinitions']['add']         = 'Add more';

	registerTabHandler ('tasksdefinitions', 'default',     'renderTasksDefinitions');
	registerTabHandler ('tasksdefinitions', 'add',         'renderTasksDefinitions');

	/* Tasks Definition in Tasks Definitions */
	$page['tasksdefinition']['title']       = 'Task Definition';
	$page['tasksdefinition']['parent']      = 'tasksdefinitions';
	$page['tasksdefinition']['bypass']      = 'task_definition_id';
	$page['tasksdefinition']['bypass_type'] = 'natural';

	$tab['tasksdefinition']['default'] = 'View';
	$tab['tasksdefinition']['edit']    = 'Properties';

	registerTabHandler ('tasksdefinition', 'default', 'renderTasksDefinition');
	registerTabHandler ('tasksdefinition', 'edit',    'renderTasksDefinition');

	registerOpHandler ('tasksdefinition', 'edit', 'add', 'addTasksDefinition');
	registerOpHandler ('tasksdefinition', 'edit', 'upd', 'updTasksDefinition');

	$interface_requires['tasksdefinitions-*'] = 'interface-config.php';
	$interface_requires['tasksdefinition-*'] = 'interface-config.php';
}

function addTasksDefinition () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	$id = insertTasksDefinition
	(
		plugin_tasks_assert ('name', 'string'),
		plugin_tasks_assert ('description', 'string'),
		plugin_tasks_assert ('enabled', 'enum/yesno'),
		plugin_tasks_assert ('frequency_id', 'uint0'),
		plugin_tasks_assert ('start_time', 'datetime'),
		plugin_tasks_assert ('mode', 'enum/mode'),
		plugin_tasks_assert ('object_id', 'uint0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}

function updTasksDefinition () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	updateTasksDefinition
	(
		plugin_tasks_assert ('id', 'uint'),
		plugin_tasks_assert ('name', 'string'),
		plugin_tasks_assert ('description', 'string'),
		plugin_tasks_assert ('enabled', 'enum/yesno'),
		plugin_tasks_assert ('frequency_id', 'uint0'),
		plugin_tasks_assert ('mode', 'enum/mode'),
		plugin_tasks_assert ('object_id', 'uint0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}
