<?php

$initTasksNavigation[] = 'initTasksNavigationTasksItem';

function initTasksNavigationTasksItem() {
	global $interface_requires, $opspec_list, $page, $tab, $trigger, $tabhandlers_stack;

	/* TasksItem */
	$page   ['tasksitem']['title']       = 'Task Item';
	$page   ['tasksitem']['bypass']      = 'task_item_id';
	$page   ['tasksitem']['bypass_type'] = 'natural';
	$page   ['tasksitem']['bypass_tabs'] = array('default', 'edit');
	$tab    ['tasksitem']['default'] = 'View';
	$tab    ['tasksitem']['edit']    = 'Properties';

	registerTabHandler ('tasksitem', 'default', 'renderTasksItem');
	registerTabHandler ('tasksitem', 'edit',    'renderTasksItem');
	registerOpHandler  ('tasksitem', 'edit', 'upd', 'updTasksItem');

	/* Tasks in Object */
	$tab    ['object']['tasksitem'] = 'Tasks';
	$trigger['object']['tasksitem'] = 'triggerTasksItems';

	registerTabHandler ('object', 'default',   'renderTasksItems', 'before');
	registerTabHandler ('object', 'tasksitem', 'renderTasksItems');

	//registerOpHandler ('object', 'tasksitem', 'add', 'addTasksItem');
	registerOpHandler ('object', 'tasksitem', 'upd', 'updTasksItem');
	//registerOpHandler ('object', 'tasksitem', 'del', 'delTasksItem');

	$interface_requires['tasksitem-*'] = 'interface-config.php';
}

function updTasksItem () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	$ret = updateTasksItem
	(
		assertTasksParam ('id', 'uint'),
		assertTasksParam ('completed', 'enum/yesno'),
		assertTasksParam ('notes', 'string0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
	return $ret;
}
