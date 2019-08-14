<?php

$initTasksNavigation[] = 'initTasksNavigationTasks';

function initTasksNavigationTasks() {
	global $interface_requires, $opspec_list, $page, $tab, $trigger;

	/* Tasks Top Tab */
	$page['tasks']['title']        = 'Tasks';
	$page['tasks']['default']      = 'default';
	$page['tasks']['parent']       = 'index';

	$tab ['tasks']['default']     = 'Outstanding';
	$tab ['tasks']['history']     = 'History';
	$tab ['tasks']['frequencies'] = 'Frequencies';

	registerTabHandler ('tasks', 'default',        'renderTasksItems');
	registerTabHandler ('tasks', 'history',        'renderTasksItems');
	registerOpHandler  ('tasks', 'default', 'upd', 'updTasksItem');

	$interface_requires['tasks-*'] = 'interface-config.php';
}
