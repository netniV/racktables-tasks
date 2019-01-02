<?php
require_once(__DIR__ . '/inc/database.php');
require_once(__DIR__ . '/inc/functions.php');

/* Dynamic section loaders */
require_once(__DIR__ . '/inc/navigation.php');
require_once(__DIR__ . '/inc/renderers.php');

function plugin_tasks_info ()
{
	return array
	(
		'name' => 'tasks',
		'longname' => 'Tasks',
		'version' => '1.0',
		'home_url' => 'http://www.github.com/netniv/racktables-tasks/'
	);
}

function tasks_exception_error_handler($errno, $errstr, $errfile, $errline ) {
	if (error_reporting()) {
		$message = "Unexpected error $errno @ $errline in $errfile : $errstr";
		$backtrace = debug_backtrace();
		error_log($message);
		$basedir = realpath(__DIR__ . '/../../');
		foreach ($backtrace as $trace) {
			$file = str_replace($basedir, '', $trace['file']);
			$func = (isset($trace['class']) ? ($trace['class'] . '::') : '') . $trace['function'];
			error_log("{$file}[{$trace['line']}] $func");
		}

		if (!empty(trim(file_get_contents(__DIR__ . '/.debug')))) {
			throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
		}
	}
}

function plugin_tasks_init ()
{
	if (file_exists(__DIR__ . '/.debug')) {
		set_error_handler("tasks_exception_error_handler");
	}


	global $interface_requires, $opspec_list, $page, $tab, $trigger;

	initTasksNavigation();

	registerHook ('resetObject_hook', 'plugin_tasks_resetObject');
	registerHook ('resetUIConfig_hook', 'plugin_tasks_resetUIConfig');
	registerHook ('dynamic_title_decoder', 'plugin_tasks_decodeTitle', 'before');

	global $plugin_tasks_fkeys;
	$plugin_tasks_fkeys = array (
		array ('fkey_name' => 'TasksItem-FK-object_id', 'table_name' => 'TasksItem'),
		array ('fkey_name' => 'TasksItem-FK-definition_id', 'table_name' => 'TasksItem'),
		array ('fkey_name' => 'TasksDefinition-FK-object_id', 'table_name' => 'TasksDefinition'),
		array ('fkey_name' => 'TasksDefinition-FK-frequency_id', 'table_name' => 'TasksDefinition'),
	);
}

function plugin_tasks_enable ()
{
	global $dbxlink;

	// Add tasks to top tabs
	$dbxlink->query("UPDATE `Config` SET varvalue=CONCAT(varvalue,',tasks')
		WHERE varname = 'QUICK_LINK_PAGES'
		AND varvalue NOT LIKE '%,tasks'
		AND varvalue NOT LIKE '%,tasks,%';");

	return TRUE;
}

function plugin_tasks_install ()
{
	global $dbxlink;

	$dbxlink->query ("
CREATE TABLE IF NOT EXISTS `TasksFrequency` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` char(64) DEFAULT NULL,
 `format` char(255) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB");

	$dbxlink->query ("
INSERT INTO TasksFrequency (name, format) VALUES ('Daily','tomorrow');
INSERT INTO TasksFrequency (name, format) VALUES ('Daily @ Noon','tomorrow 12:00');
INSERT INTO TasksFrequency (name, format) VALUES ('First Tuesday','first tuesday of next month');
INSERT INTO TasksFrequency (name, format) VALUES ('Last Thursday','last thursday of next month');
INSERT INTO TasksFrequency (name, format) VALUES ('Every Friday','next friday');
INSERT INTO TasksFrequency (name, format) VALUES ('Every Monday','next monday');
INSERT INTO TasksFrequency (name, format) VALUES ('Every Wednesday','next wednesday');
INSERT INTO TasksFrequency (name, format) VALUES ('Monthly 1st','first day of this month; next month');
INSERT INTO TasksFrequency (name, format) VALUES ('Monthly 15th','first day of this month; next month; +15 days');
INSERT INTO TasksFrequency (name, format) VALUES ('Quarterly','first day of this month, +3 months midnight');
INSERT INTO TasksFrequency (name, format) VALUES ('Semi-Annual 1st','first day of this month, +6 months midnight');
");
	$dbxlink->query ("
CREATE TABLE IF NOT EXISTS `TasksDefinition` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` char(64) DEFAULT NULL,
 `description` char(255) DEFAULT NULL,
 `details` text DEFAULT NULL,
 `mode` enum('due', 'schedule') NOT NULL DEFAULT 'due',
 `enabled` enum('yes','no') NOT NULL DEFAULT 'no',
 `frequency_id` int(10) unsigned NOT NULL,
 `object_id` int(10) unsigned NOT NULL,
 `start_time` timestamp NULL,
 `processed_time` timestamp NULL,
 `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 KEY `object_id` (`object_id`),
 KEY `frequency_id` (`frequency_id`),
 INDEX `mode` (`mode`),
 CONSTRAINT `TasksDefinition-FK-frequency_id` FOREIGN KEY (`frequency_id`) REFERENCES `TasksFrequency` (`id`)
) ENGINE=InnoDB");

	$dbxlink->query	("
CREATE TABLE IF NOT EXISTS `TasksItem`(
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `definition_id` int(10) unsigned NOT NULL,
 `object_id` int(10) unsigned NOT NULL,
 `user_name` varchar(24) NOT NULL DEFAULT '',
 `mode` enum('due', 'schedule') NOT NULL DEFAULT 'due',
 `name` char(64) DEFAULT NULL,
 `description` char(255) DEFAULT NULL,
 `completed` enum('yes','no') NOT NULL DEFAULT 'no',
 `completed_time` timestamp NULL DEFAULT NULL,
 `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `notes` TEXT DEFAULT NULL,
 PRIMARY KEY (`id`,`object_id`),
 KEY `object_id` (`object_id`),
 KEY `user_name` (`user_name`),
 KEY `TasksItem-FK-definition_id` (`definition_id`),
 CONSTRAINT `TasksItem-FK-definition_id` FOREIGN KEY (`definition_id`) REFERENCES `TasksDefinition` (`id`)
) ENGINE=InnoDB");

//SELECT * FROM `Config` WHERE varname = 'QUICK_LINK_PAGES' AND varvalue NOT LIKE '%,tasks' and varvalue NOT LIKE '%,tasks,%';

	addConfigVar ('TASKS_LISTSRC', 'false', 'string', 'yes', 'no', 'no', 'List of object with Tasks');
//	addConfigVar ('CACTI_RRA_ID', '1', 'uint', 'no', 'no', 'yes', 'RRA ID for Tasks graphs displayed in RackTables');

	plugin_tasks_enable();

	return TRUE;
}

function plugin_tasks_disable ()
{
	// Add tasks to top tabs
	$dbxlink->query("UPDATE `Config` SET varvalue=REPLACE(varvalue,',tasks','')
		WHERE varname = 'QUICK_LINK_PAGES'
		AND varvalue LIKE '%,tasks'
		AND varvalue LIKE '%,tasks,%';");
}

function plugin_tasks_uninstall ()
{
	deleteConfigVar ('TASKS_LISTSRC');
//	deleteConfigVar ('CACTI_RRA_ID');

	global $dbxlink;
	$dbxlink->query	("DROP TABLE `TasksItem`");
	$dbxlink->query	("DROP TABLE `TasksDefinition`");

	plugin_tasks_disable();

	return TRUE;
}

function plugin_tasks_upgrade ()
{
	return TRUE;
}

/*
function plugin_tasks_dispatchImageRequest ()
{
	global $pageno, $tabno;

	if ($_REQUEST['img'] == 'tasksgraph')
	{
		$pageno = 'object';
		$tabno = 'tasks';
		fixContext ();
		assertPermission ();
		$task_id = assertTasksParam ('graph_id', 'natural');
		if (! array_key_exists ($task_id, getTasksItemsForObject (getBypassValue())))
			throw new InvalidRequestArgException ('graph_id', $task_id);
		proxyTasksRequest (assertTasksParam ('definition_id', 'natural'), $task_id);
	}
	return TRUE;
}
*/

function plugin_tasks_resetObject ($object_id)
{
	usePreparedDeleteBlade ('TasksItem', array ('object_id' => $object_id));
	usePreparedDeleteBlade ('TasksDefinition', array ('object_id' => $object_id));
}

function plugin_tasks_resetUIConfig ()
{
	setConfigVar ('TASKS_LISTSRC', 'false');
//	setConfigVar ('CACTI_RRA_ID', '1');
}

function plugin_tasks_decodeTitle($no) {
	global $page, $tab;

	$title = array();
	if ($no == 'tasks:definitionstab') {
		$title = array(
			'name' => 'Tasks',
			'params' => array(
				'page' => 'tasks',
				'tab' => 'default',
			)
		);
		recordTasksDebug("plugin_tasks_decodeTitle: Handled $no - " . json_encode($title));
	}

	if ($no == 'object:tasks') {
		$obj = false;
		$object_id =0;
		if (isset($_REQUEST['object_id'])) {
			$object_id = $_REQUEST['object_id'];
			$obj = spotEntity('object', $object_id);
		} elseif (isset($_REQUEST['task_item_id'])) {
			$obj = getTasksItems (0, NULL, $_REQUEST['task_item_id']);
			if ($obj) {
				$obj = reset($obj);
				$obj['dname'] = $obj['object_name'];
				$object_id = $obj['object_id'];
			}
		} elseif (isset($_REQUEST['task_definition_id'])) {
			$obj = getTasksDefinitions ($_REQUEST['task_definition_id']);
			if ($obj) {
				$obj = reset($obj);
				$obj['dname'] = $obj['object_name'];
				$object_id = $obj['object_id'];
			}
		}

		if ($obj) {
			$title = array(
				'name'   => $obj['dname'],
				'params' => array(
					'page' => 'object',
					'object_id' => $object_id
				)
			);				
		}
	}

	if (!empty($title)) {
		stopHookPropagation ();
		recordTasksDebug('decodeTitle("' . $no . '"): returned ' . json_encode($title));
		return $title;
	}

	if (!in_array($no, array('object','ipv4space'))) {
		recordTasksDebug('decodeTitle("' . $no . '"): unhandled');
	}
	return $no;
}
