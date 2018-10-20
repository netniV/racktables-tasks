<?php
require_once(__DIR__ . '/inc/database.php');
require_once(__DIR__ . '/inc/functions.php');
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

function plugin_tasks_init ()
{
	global $interface_requires, $opspec_list, $page, $tab, $trigger;

	initTasksNavigationTasks();
	initTasksNavigationTasksDefinition();
	initTasksNavigationTasksFrequency();
	initTasksNavigationTasksItem();

	registerHook ('resetObject_hook', 'plugin_tasks_resetObject');
	registerHook ('resetUIConfig_hook', 'plugin_tasks_resetUIConfig');
	registerHook ('modifyEntitySummary', 'plugin_tasks_modifyEntitySummary');
	registerHook ('dynamic_title_decoder', 'plugin_tasks_decodeTitle');
	addCSS("
	@import url('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
	@import url('https://npmcdn.com/flatpickr/dist/themes/material_blue.css');
	", true);

	global $plugin_tasks_fkeys;
	$plugin_tasks_fkeys = array (
		array ('fkey_name' => 'TasksItem-FK-object_id', 'table_name' => 'TasksItem'),
		array ('fkey_name' => 'TasksItem-FK-definition_id', 'table_name' => 'TasksItem'),
		array ('fkey_name' => 'TasksDefinition-FK-object_id', 'table_name' => 'TasksDefinition'),
		array ('fkey_name' => 'TasksDefinition-FK-frequency_id', 'table_name' => 'TasksDefinition'),
	);
}

function plugin_tasks_assert ($argname, $argtype) {
	global $sic;
	switch ($argtype) {
		case 'frequency':
			try {
				$freq = parseTasksFrequency($sic[$argname]);
				$date_orig = new DateTime();
				$date_freq = clone $date_orig;

				$date_freq = getTasksNextDue($freq['data'], $date_orig);
			} catch (Exception $e) {
				throw new InvalidRequestArgException($argname, $sic[$argname], $e->getMessage());
			}

			$stamp_orig = $date_orig->getTimestamp();
			$stamp_freq = $date_freq->getTimestamp();

			if ($stamp_freq == $stamp_orig) {
				throw new InvalidRequestArgException($argname, $sic[$argname], 'does not modify date: ' . $stamp_freq . ' = ' . $stamp_orig);
			} else if ($stamp_freq < $stamp_orig) {
				throw new InvalidRequestArgException($argname, $sic[$argname], 'goes backwards: ' . $stamp_freq . ' < ' . $stamp_orig);
			}

			return $sic[$argname];
		case 'enum/mode':
			if (! array_key_exists ($sic[$argname], getTasksModes()))
				throw new InvalidRequestArgException ($argname, $sic[$argname], 'Unknown value');
			return $sic[$argname];
		default:
			return genericAssertion($argname, $argtype);
	}
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
CREATE TABLE IF NOT EXISTS `TasksDefinition` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` char(64) DEFAULT NULL,
 `description` char(255) DEFAULT NULL,
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
 `notes` char(255) DEFAULT NULL,
 PRIMARY KEY (`id`,`object_id`),
 KEY `object_id` (`object_id`),
 KEY `user_name` (`user_name`),
 KEY `TasksItem-FK-definition_id` (`definition_id`),
 CONSTRAINT `TasksItem-FK-definition_id` FOREIGN KEY (`definition_id`) REFERENCES `TasksDefinition` (`id`)
) ENGINE=InnoDB");

//SELECT * FROM `Config` WHERE varname = 'QUICK_LINK_PAGES' AND varvalue NOT LIKE '%,tasks' and varvalue NOT LIKE '%,tasks,%';

	// Add tasks to top tabs
	$dbxlink->query("UPDATE `Config` SET varvalue=CONCAT(varvalue,',tasks')
		WHERE varname = 'QUICK_LINK_PAGES'
		AND varvalue NOT LIKE '%,tasks'
		AND varvalue NOT LIKE '%,tasks,%';");

	addConfigVar ('TASKS_LISTSRC', 'false', 'string', 'yes', 'no', 'no', 'List of object with Tasks');
//	addConfigVar ('CACTI_RRA_ID', '1', 'uint', 'no', 'no', 'yes', 'RRA ID for Tasks graphs displayed in RackTables');

	return TRUE;
}

function plugin_tasks_uninstall ()
{
	deleteConfigVar ('TASKS_LISTSRC');
//	deleteConfigVar ('CACTI_RRA_ID');

	global $dbxlink;
	$dbxlink->query	("DROP TABLE `TasksItem`");
	$dbxlink->query	("DROP TABLE `TasksDefinition`");

	// Add tasks to top tabs
	$dbxlink->query("UPDATE `Config` SET varvalue=REPLACE(varvalue,',tasks','')
		WHERE varname = 'QUICK_LINK_PAGES'
		AND varvalue LIKE '%,tasks'
		AND varvalue LIKE '%,tasks,%';");
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
		$task_id = plugin_tasks_assert ('graph_id', 'natural');
		if (! array_key_exists ($task_id, getTasksItemsForObject (getBypassValue())))
			throw new InvalidRequestArgException ('graph_id', $task_id);
		proxyTasksRequest (plugin_tasks_assert ('definition_id', 'natural'), $task_id);
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

function plugin_tasks_modifyEntitySummary ($cell, $values) {
	return $values;
}

function plugin_tasks_decodeTitle($tab) {
	if ($tab == 'tasks:definitionstab') {
		return array('name' => 'Tasks', 'params' => array('page' => 'tasks', 'tab' => 'definitions'));
	}
	return array('name' => $tab);
}
