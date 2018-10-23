<?php

$initTasksNavigation[] = 'initTasksNavigationTasksFrequency';

function initTasksNavigationTasksFrequency () {
	global $interface_requires, $opspec_list, $page, $tab, $trigger;

	$tab ['tasks']['frequencies'] = 'Frequencies';
	registerTabHandler ('tasks', 'frequencies', 'renderTasksFrequencies');
	registerOpHandler  ('tasks', 'frequencies', 'add', 'addTasksFrequency');

	/* Tasks Frequenies */
	$page['tasksfrequencies']['title']  = 'Task Frequenies';
	$page['tasksfrequencies']['parent'] = 'tasks:frequencies';

	$tab['tasksfrequencies']['default'] = 'Browse';
	//$tab['tasksfrequencies']['add']     = 'Add more';

	registerTabHandler ('tasksfrequencies', 'default', 'renderTasksFrequencies');
	registerTabHandler ('tasksfrequencies', 'add',     'renderTasksFrequencies');

	registerOpHandler  ('tasksfrequencies', 'default', 'add', 'addTasksFrequency');

	$interface_requires['tasksfrequencies-*'] = 'interface-config.php';

	/* Tasks Frequency */
	$page['tasksfrequency']['title']       = 'Task Frequency';
	$page['tasksfrequency']['parent']      = 'tasksfrequencies';
	$page['tasksfrequency']['bypass']      = 'task_freqeuency_id';
	$page['tasksfrequency']['bypass_type'] = 'uint';

	$tab['tasksfrequency']['default'] = 'View';
	$tab['tasksfrequency']['edit']    = 'Properties';

	registerTabHandler ('tasksfrequency', 'default', 'renderTasksFrequency');
	registerTabHandler ('tasksfrequency', 'edit',    'renderTasksFrequency');

	registerOpHandler  ('tasksfrequency', 'default', 'add', 'updTasksFrequency');
	registerOpHandler  ('tasksfrequency', 'edit',    'upd', 'updTasksFrequency');

	$interface_requires['tasksfrequency-*'] = 'interface-config.php';
}

function addTasksFrequency () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	$id = insertTasksFrequency
	(
		assertTasksParam ('name', 'string'),
		assertTasksParam ('format', 'frequency')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}

function updTasksFrequency () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	updateTasksFrequency
	(
		assertTasksParam ('id', 'uint'),
		assertTasksParam ('name', 'string'),
		assertTasksParam ('format', 'frequency')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}
