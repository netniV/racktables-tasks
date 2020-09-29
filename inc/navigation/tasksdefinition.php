<?php

$initTasksNavigation[] = 'initTasksNavigationTasksDefinition';

function initTasksNavigationTasksDefinition() {
	global $interface_requires, $ophandler, $page, $tab, $trigger;

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
//	$page['tasksdefinition']['title']       = 'Task Definition';
	$page['tasksdefinition']['parent']      = 'tasksdefinitions';
	$page['tasksdefinition']['bypass']      = 'task_definition_id';
	$page['tasksdefinition']['bypass_type'] = 'uint';
	$page['tasksdefinition']['bypass_tabs'] = array('default', 'edit');

	$tab['tasksdefinition']['default'] = 'View';
	$tab['tasksdefinition']['edit']    = 'Properties';

	registerTabHandler ('tasksdefinition', 'default', 'renderTasksDefinition');
	registerTabHandler ('tasksdefinition', 'edit',    'renderTasksDefinition');

	registerOpHandler ('tasksdefinitions', 'default', 'add', 'addTasksDefinition');
	registerOpHandler ('tasksdefinition', 'edit', 'add', 'addTasksDefinition');
	registerOpHandler ('tasksdefinition', 'edit', 'upd', 'updTasksDefinition');

	$interface_requires['tasksdefinitions-*'] = 'interface-config.php';
	$interface_requires['tasksdefinition-*'] = 'interface-config.php';
}

function addTasksDefinition () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	$id = insertTasksDefinition
	(
		assertTasksParam ('name', 'string'),
		assertTasksParam ('description', 'string'),
		assertTasksParam ('enabled', 'enum/yesno'),
		assertTasksParam ('frequency_id', 'uint0'),
		assertTasksParam ('start_time', 'datetime'),
		assertTasksParam ('type', 'enum/mode'),
		assertTasksParam ('object_id', 'uint0'),
		assertTasksParam ('details', 'string0'),
		assertTasksParam ('repeat','enum/yesno'),
		assertTasksParam ('department', 'string0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}

function updTasksDefinition () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	updateTasksDefinition
	(
		assertTasksParam ('id', 'uint'),
		assertTasksParam ('name', 'string'),
		assertTasksParam ('description', 'string'),
		assertTasksParam ('enabled', 'enum/yesno'),
		assertTasksParam ('frequency_id', 'uint0'),
		assertTasksParam ('mode', 'enum/mode'),
		assertTasksParam ('object_id', 'uint0'),
		assertTasksParam ('details', 'string0'),
		assertTasksParam ('repeat', 'enum/yesno'),
		assertTasksParam ('department', 'string0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}
