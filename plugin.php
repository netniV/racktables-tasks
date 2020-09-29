<?php
require_once(__DIR__ . '/inc/database.php');
require_once(__DIR__ . '/inc/functions.php');
require_once(__DIR__ . '/inc/compat.php');

/* Dynamic section loaders */
require_once(__DIR__ . '/inc/navigation.php');
require_once(__DIR__ . '/inc/renderers.php');

function plugin_tasks_info ()
{
	return array
	(
		'name' => 'tasks',
		'longname' => 'Tasks',
		'version' => '1.4',
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
			if (isset($trace['file'])) {
				$file = str_replace($basedir, '', $trace['file']);
			} else {
				$file = 'unknown';
			}

			$func = (isset($trace['class']) ? ($trace['class'] . '::') : '') . $trace['function'];
			$line = (isset($trace['line']) ? $trace['line'] : '');
			error_log("{$file}[{$line}] $func");
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

function plugin_tasks_vars ()
{
	static $plugin_tasks_vars;

	if (empty($plugin_tasks_vars)) {
		$plugin_tasks_vars = array(
			'1.1' => array(
				array('name' => 'TASK_LISTSRC',    'type' => 'string', 'default' => 'false', 'desc' => 'List of object with Tasks'),
				array('name' => 'TASKS_HIDE_MODE', 'type' => 'string', 'default' => 'false', 'desc' => 'Hide mode column when displaying tasks'),
				array('name' => 'TASKS_DATE_ONLY', 'type' => 'string', 'default' => 'false', 'desc' => 'Show dates (no time) when not vertiical'),
			),
			'1.2' => array(
				array('name' => 'TASKS_HIDE_ID',       'type' => 'string', 'default' => 'false', 'desc' => 'Hide ID column when displaying tasks'),
				array('name' => 'TASKS_TEXT_DUE',      'type' => 'string', 'default' => 'next due',      'desc' => 'Text for Frequency type'),
				array('name' => 'TASKS_TEXT_SCHEDULE', 'type' => 'string', 'default' => 'scheduled',     'desc' => 'Text for Schedule type'),
				array('name' => 'TASKS_TEXT_COMPLETE', 'type' => 'string', 'default' => 'on completion', 'desc' => 'Text for Completion type'),
			),
			'1.4' => array(
				array('name' => 'TASKS_DEPARTMENTS',   'type' => 'string', 'default' => 'Sales,Support,Technology,Facilities,Compliance,Accounting', 'desc' => 'Comma separated departments'),
			),
		);
	}

	return $plugin_tasks_vars;
}

function plugin_tasks_install ()
{
	global $dbxlink;

	$dbxlink->query ("
CREATE TABLE IF NOT EXISTS `TasksFrequency` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` char(64) DEFAULT NULL,
 `format` char(255) DEFAULT NULL,
 PRIMARY KEY (`id`),
 CONSTRAINT uk_name UNIQUE (`name`)
) ENGINE=InnoDB");

	$dbxlink->query ("
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Daily','tomorrow');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Daily @ Noon','tomorrow 12:00');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('First Tuesday','first tuesday of next month');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Last Thursday','last thursday of next month');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Every Friday','next friday');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Every Monday','next monday');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Every Wednesday','next wednesday');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Monthly 1st','first day of this month; next month');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Monthly 15th','first day of this month; next month; +15 days');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Quarterly','first day of this month, +3 months midnight');
INSERT IGNORE INTO TasksFrequency (name, format) VALUES ('Semi-Annual 1st','first day of this month, +6 months midnight');
");
	$dbxlink->query ("
CREATE TABLE IF NOT EXISTS `TasksDefinition` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` char(64) DEFAULT NULL,
 `description` char(255) DEFAULT NULL,
 `details` text DEFAULT NULL,
 `mode` enum('due', 'schedule', 'complete') NOT NULL DEFAULT 'due',
 `enabled` enum('yes','no') NOT NULL DEFAULT 'no',
 `frequency_id` int(10) unsigned NOT NULL,
 `object_id` int(10) unsigned NOT NULL,
 `start_time` timestamp NULL,
 `processed_time` timestamp NULL,
 `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `repeat` enum('yes','no') NOT NULL DEFAULT 'yes',
 `department` varchar(40) NOT NULL DEFAULT '',
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
 `mode` enum('due', 'schedule', 'complete') NOT NULL DEFAULT 'due',
 `name` char(64) DEFAULT NULL,
 `description` char(255) DEFAULT NULL,
 `completed` enum('yes','no') NOT NULL DEFAULT 'no',
 `completed_time` timestamp NULL DEFAULT NULL,
 `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `notes` TEXT DEFAULT NULL,
 `department` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`id`,`object_id`),
 KEY `object_id` (`object_id`),
 KEY `user_name` (`user_name`),
 KEY `TasksItem-FK-definition_id` (`definition_id`),
 CONSTRAINT `TasksItem-FK-definition_id` FOREIGN KEY (`definition_id`) REFERENCES `TasksDefinition` (`id`)
) ENGINE=InnoDB");

	// Add tasks to top tabs
	$dbxlink->query("UPDATE `Config` SET varvalue=CONCAT(varvalue,',tasks')
		WHERE varname = 'QUICK_LINK_PAGES'
		AND varvalue NOT LIKE '%,tasks'
		AND varvalue NOT LIKE '%,tasks,%';");

	$dbxlink->query("UPDATE `Config` SET varvalue=REPLACE(varvalue,',,',',')
		WHERE varname = 'QUICK_LINK_PAGES';");

	plugin_tasks_vars_add();

	return TRUE;
}

function plugin_tasks_vars_add ($reset = false) {
	recordTasksDebug('plugin_tasks_vars_add(): Start');
	$configVars = plugin_tasks_vars();
	foreach ($configVars as $ver => $vars) {
		recordTasksDebug('plugin_tasks_vars_add(): v' . $ver);

		foreach ($vars as $var) {
			$exists = existsConfigVar($var['name']);
			recordTasksDebug('plugin_tasks_vars_add: ['.$var['name'].'] ' . ($exists?'':'NOT '). ' exists');
			if (!$exists) {
				recordTasksDebug('plugin_tasks_vars_add: ['.$var['name'].'] addConfigVar ("' . $var['name'] .
					'", "' . $var['default'] . '", "' . $var['type'] . '", "yes", "no", "no", "' . $var['desc'] .
					'");');
				addConfigVar ($var['name'], $var['default'],  $var['type'], "yes", "no", "no", $var['desc']);
			} elseif ($reset) {
				recordTasksDebug('plugin_tasks_vars_add: ['.$var['name'].'] setConfigVar ("' . $var['name'] .
					'", "' . $var['default'] . '");');
				setConfigVar ($var['name'], $var['default']);
			}
		}
	}
	recordTasksDebug('plugin_tasks_vars_add(): End');
}

function plugin_tasks_vars_delete () {
	recordTasksDebug('plugin_tasks_vars_delete(): Start');
	$configVars = plugin_tasks_vars();
	foreach ($configVars as $ver => $vars) {
		recordTasksDebug('plugin_tasks_vars_delete(): v' + $ver);

		foreach ($vars as $var) {
			$exists = existsConfigVar($var['name']);
			recordTasksDebug('plugin_tasks_vars_delete('.$ver.'): ['.$var['name'].'] ' . ($exists?'':'NOT '). ' exists');
			if (!existsConfigVar($var['name'])) {
				recordTasksDebug('plugin_tasks_vars_delete: ['.$var['name'].'] deleteConfigVar ("' . $var['name'] .
					'");');
				deleteConfigVar ($var['name']);
			}
		}
	}
	recordTasksDebug('plugin_tasks_vars_delete(): End');
}

function plugin_tasks_uninstall ()
{
	plugin_tasks_vars_delete();

	global $dbxlink;

	// Add tasks to top tabs
	$dbxlink->query("UPDATE `Config` SET varvalue=REPLACE(varvalue,',tasks','')
		WHERE varname = 'QUICK_LINK_PAGES'
		AND varvalue LIKE '%,tasks'
		AND varvalue LIKE '%,tasks,%';");

	$dbxlink->query	("DROP TABLE `TasksFrequency`");
	$dbxlink->query	("DROP TABLE `TasksItem`");
	$dbxlink->query	("DROP TABLE `TasksDefinition`");

	return TRUE;
}

function plugin_tasks_upgrade ()
{
	$db_info = getPlugin('tasks');
	$v1 = $db_info['db_version'];
	$code_info = plugin_tasks_info();
	$v2 = $code_info['version'];

	if ($v1 == $v2) return TRUE;

	$versionhistory = array
	(
		'1.0',
		'1.1',
		'1.2',
		'1.3',
		'1.4',
	);

	$skip = TRUE;
	$path = NULL;

	foreach ($versionhistory as $vh)
	{
		if ($skip && ($vh == $v1))
		{
			$skip = FALSE;
			$path = array();
			continue;
		}

		if ($skip) continue;

		$path[] = $vh;
		if ($vh == $v2) break;
	}

	if ($path == NULL || !count($path))
		throw new RackTablesError ('Unable to determine upgrade path', RackTablesError::INTERNAL);

	// build the list of queries to execute

	$queries = array();
	foreach ($path as $v)
	{
		switch ($v)
		{
			case '1.0':
				break;

			case '1.1':
				break;

			case '1.2':
				$queries[] = "ALTER TABLE `TasksItem` MODIFY `mode` enum('due', 'schedule', 'complete') NOT NULL DEFAULT 'due'";
				$queries[] = "ALTER TABLE `TasksDefinition` MODIFY `mode` enum('due', 'schedule', 'complete') NOT NULL DEFAULT 'due'";
				$queries[] = "ALTER TABLE `TasksFrequency` ADD  CONSTRAINT uk_name UNIQUE (`name`);";
				break;

			case '1.3':
				$queries[] = "ALTER TABLE `TasksDefinition` ADD  `repeat` enum('yes','no') NOT NULL DEFAULT 'yes'";
				break;

			case '1.4':
				$queries[] = "ALTER TABLE `TasksDefinition` ADD `department` varchar(40) NOT NULL DEFAULT ''";
				$queries[] = "ALTER TABLE `TasksItem` ADD `department` varchar(40) DEFAULT NULL";
				break;

			default:
				throw new RackTablesError("Preparing to upgrade to $v failed", RackTablesError::INTERNAL);
		}
		$queries[] = "UPDATE Plugin SET version = '$v' WHERE name = 'tasks'";
	}

	plugin_tasks_vars_add ();

	// execute the queries
	global $dbxlink;
	foreach ($queries as $q)
	{
		try
		{
			$result = $dbxlink->query ($q);
		}
		catch (PDOException $e)
		{
			$errorInfo = $dbxlink->errorInfo();
			throw new RackTablesError ("Query: ${errorInfo[2]}", RackTablesError::INTERNAL);
		}
	}

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
	plugin_tasks_vars_add (true);
}

function plugin_tasks_decodeTitle($no) {
	global $page, $tab;

	$title = array();
	if ($no == 'tasks:definitionstab' || $no == 'tasks:frequenciestab') {
		$title = array(
			'name' => 'Tasks',
			'params' => array(
				'page' => 'tasks',
				'tab' => 'default',
			)
		);
		//recordTasksDebug("plugin_tasks_decodeTitle: Handled $no - " . json_encode($title));
	}

	if ($no == 'object:tasks') {
		$obj = false;
		$object_id =0;
		if (isset($_REQUEST['object_id'])) {
			$mode = 'object_id';
			$object_id = $_REQUEST['object_id'];
			$obj = spotEntity('object', $object_id);
		} elseif (isset($_REQUEST['task_item_id'])) {
			$mode = 'task_item_id';
			$obj = getTasksItems (0, NULL, $_REQUEST['task_item_id']);
			if ($obj) {
				$obj = reset($obj);
				$obj['dname'] = $obj['object_name'];
				$object_id = $obj['object_id'];
			}
		} elseif (isset($_REQUEST['task_definition_id'])) {
			$mode = 'task_definition_id';
			$obj = getTasksDefinitions ($_REQUEST['task_definition_id']);
			if ($obj) {
				$obj = reset($obj);
				$obj['dname'] = $obj['object_name'];
				$object_id = $obj['object_id'];
			}
		}

		recordTasksDebug('**OBJ** ' . json_encode(var_export($obj,true)));
		if ($obj) {
			if (empty($obj['dname'])) {
				$obj['dname'] = 'Object ' . $object_id;
			}

			$title = array(
				'name'   => $obj['dname'],
				'mode'   => $mode,
				'params' => array(
					'page' => 'object',
					'object_id' => $object_id
				)
			);
		}
	} elseif ($no == 'tasksfrequency') {
		recordTasksDebug ('REQUEST: ' . json_encode(var_export($_REQUEST, true)));
		$mode = 'tasksfrequency_id';
		$obj = getTasksFrequencies ($_REQUEST['task_frequency_id']);
		if ($obj) {
			$obj = reset($obj);
			$title = array(
				'name'   => $obj['name'],
				'mode'   => $mode,
				'params' => array(
					'page' => 'tasksfrequency',
					'task_frequency_id' => $obj['id'],
				)
			);
		}
	} elseif ($no == 'tasksdefinition') {
		recordTasksDebug ('REQUEST: ' . json_encode(var_export($_REQUEST, true)));
		$mode = 'tasksdefinition_id';
		$obj = getTasksDefinitions ($_REQUEST['task_definition_id']);
		if ($obj) {
			$obj = reset($obj);
			$title = array(
				'name'   => $obj['name'],
				'mode'   => $mode,
				'params' => array(
					'page' => 'tasksdefinition',
					'task_definition_id' => $obj['id'],
				)
			);
		}
	} elseif ($no == 'tasksitem') {
		recordTasksDebug ('REQUEST: ' . json_encode(var_export($_REQUEST, true)));
		$mode = 'tasksitem_id';
		$obj = getTasksItems (0, NULL, $_REQUEST['task_item_id']);
		if ($obj) {
			$obj = reset($obj);
			$title = array(
				'name'   => $obj['name'],
				'mode'   => $mode,
				'params' => array(
					'page' => 'tasksitem',
					'task_item_id' => $obj['id'],
				)
			);
		} else {
			recordTasksDebug('Failed to obtain tasksitem for ' . $_REQUEST['task_item_id']);
		}
	}

	if (!empty($title)) {
		stopHookPropagation ();
		recordTasksDebug('decodeTitle("' . $no . '"): returned ' . json_encode($title));
		return $title;
	}

	if (!in_array($no, array('object','ipv4space','ipv6space'))) {
		recordTasksDebug('decodeTitle("' . $no . '"): unhandled');
	}
	return $no;
}
