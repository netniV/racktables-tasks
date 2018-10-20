<?php

function initTasksNavigationTasksItem() {
	global $interface_requires, $opspec_list, $page, $tab, $trigger, $tabhandlers_stack;

	/* TasksItem */
	$page   ['tasksitem']['title']       = 'Task Item';
	$page   ['tasksitem']['bypass']      = 'task_item_id';
	$page   ['tasksitem']['bypass_type'] = 'natural';
	$page   ['tasksitem']['bypass_tabs'] = array('default');
	$tab    ['tasksitem']['default'] = 'View';
	$tab    ['tasksitem']['edit']    = 'Properties';

	registerTabHandler ('tasksitem', 'default', 'renderTasksItem');
	registerTabHandler ('tasksitem', 'edit',    'renderTasksItem');

	/* Tasks in Object */
	$tab    ['object']['tasksitem'] = 'Tasks';
	$trigger['object']['tasksitem'] = 'triggerTasksItems';

	registerTabHandler ('object', 'default',   'renderTasksItems');
	registerTabHandler ('object', 'tasksitem', 'renderTasksItems');

	//registerOpHandler ('object', 'tasksitem', 'add', 'addTasksItem');
	registerOpHandler ('object', 'tasksitem', 'upd', 'updTasksItem');
	//registerOpHandler ('object', 'tasksitem', 'del', 'delTasksItem');

	$interface_requires['tasksitem-*'] = 'interface-config.php';
}

function updTasksItem () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	updateTasksItem
	(
		plugin_tasks_assert ('id', 'uint'),
		plugin_tasks_assert ('completed', 'enum/yesno'),
		plugin_tasks_assert ('notes', 'string0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}
