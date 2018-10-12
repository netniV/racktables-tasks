<?php

function plugin_tasks_info ()
{
	return array
	(
		'name' => 'tasks',
		'longname' => 'Tasks',
		'version' => '1.0',
		'home_url' => 'http://www.racktables.org/'
	);
}

function plugin_tasks_init ()
{
	global $interface_requires, $opspec_list, $page, $tab, $trigger;
	$tab['object']['tasksitem'] = 'Tasks';
	registerTabHandler ('object', 'tasksitem', 'renderObjectTasksItems');
	registerTabHandler ('object', 'default', 'renderObjectTasksItems');

	$trigger['object']['tasksitem'] = 'triggerTasksItems';
	registerOpHandler ('object', 'tasksitem', 'add', 'tableHandler');
	registerOpHandler ('object', 'tasksitem', 'del', 'tableHandler');

	$page['tasks']['title'] = 'Tasks';
	registerTabHandler ('tasks', 'default', 'renderTasks');
	registerOpHandler ('tasks', 'tasks', 'upd', 'tableHandler');

	$page['tasksconfig']['title'] = 'Task Definitions';
	$page['tasksconfig']['parent'] = 'config';

	$tab['tasksconfig']['default'] = 'View';
	$tab['tasksconfig']['definitions'] = 'Manage definitions';

	registerTabHandler ('tasksconfig', 'default', 'renderTasksConfig');
	registerTabHandler ('tasksconfig', 'definitions', 'renderTasksDefinitionsEditor');

	registerOpHandler ('tasksconfig', 'definitions', 'add', 'tableHandler');
	registerOpHandler ('tasksconfig', 'definitions', 'del', 'tableHandler');
	registerOpHandler ('tasksconfig', 'definitions', 'upd', 'tableHandler');

	registerOpHandler ('object', 'tasksitem', 'upd', 'updObjectTasksItem');

	$interface_requires['tasksconfig-*'] = 'interface-config.php';
	$interface_requires['tasksitem-*'] = 'interface-config.php';

	registerHook ('dispatchImageRequest_hook', 'plugin_tasks_dispatchImageRequest');
	registerHook ('resetObject_hook', 'plugin_tasks_resetObject');
	registerHook ('resetUIConfig_hook', 'plugin_tasks_resetUIConfig');

	$opspec_list['object-tasksitem-add'] = array
	(
		'table' => 'TasksItem',
		'action' => 'INSERT',
		'arglist' => array
		(
			array ('url_argname' => 'object_id', 'assertion' => 'uint'),
			array ('url_argname' => 'definition_id', 'assertion' => 'uint'),
			array ('url_argname' => 'name', 'assertion' => 'string0'),
			array ('url_argname' => 'description', 'assertion' => 'string0'),
		),
	);
	$opspec_list['object-tasksitem-del'] = array
	(
		'table' => 'TasksItem',
		'action' => 'DELETE',
		'arglist' => array
		(
			array ('url_argname' => 'object_id', 'assertion' => 'uint'),
			array ('url_argname' => 'definition_id', 'assertion' => 'uint'),
			array ('url_argname' => 'id', 'assertion' => 'uint'),
		),
	);
	$opspec_list['tasksconfig-definitions-add'] = array
	(
		'table' => 'TasksDefinition',
		'action' => 'INSERT',
		'arglist' => array
		(
			array ('url_argname' => 'name', 'assertion' => 'string'),
			array ('url_argname' => 'description', 'assertion' => 'string0'),
			array ('url_argname' => 'enabled', 'assertion' => 'enum/yesno'),
			array ('url_argname' => 'frequency', 'assertion' => 'uint'),
			array ('url_argname' => 'object_id', 'assertion' => 'uint0'),
		),
	);
	$opspec_list['tasksconfig-definitions-del'] = array
	(
		'table' => 'TasksDefinition',
		'action' => 'DELETE',
		'arglist' => array
		(
			array ('url_argname' => 'id', 'assertion' => 'uint'),
		),
	);
	$opspec_list['tasksconfig-definitions-upd'] = array
	(
		'table' => 'TasksDefinition',
		'action' => 'UPDATE',
		'set_arglist' => array
		(
			array ('url_argname' => 'name', 'assertion' => 'string'),
			array ('url_argname' => 'description', 'assertion' => 'string0'),
			array ('url_argname' => 'enabled', 'assertion' => 'enum/yesno'),
			array ('url_argname' => 'frequency', 'assertion' => 'uint'),
			array ('url_argname' => 'object_id', 'assertion' => 'uint0'),
		),
		'where_arglist' => array
		(
			array ('url_argname' => 'id', 'assertion' => 'uint'),
		),
	);

	global $plugin_tasks_fkeys;
	$plugin_tasks_fkeys = array (
		array ('fkey_name' => 'TasksItem-FK-object_id', 'table_name' => 'TasksItem'),
		array ('fkey_name' => 'TasksDefinition-FK-object_id', 'table_name' => 'TasksDefinition'),
		array ('fkey_name' => 'TasksItem-FK-definition_id', 'table_name' => 'TasksItem'),
	);
}

function plugin_tasks_install ()
{
	global $dbxlink;

	$dbxlink->query ("
CREATE TABLE IF NOT EXISTS `TasksDefinition` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` char(64) DEFAULT NULL,
 `description` char(255) DEFAULT NULL,
 `frequency` int(10) unsigned NOT NULL,
 `enabled` enum('yes','no') NOT NULL DEFAULT 'no',
 `object_id` int(10) unsigned NOT NULL,
 `processed_time` timestamp NULL,
 `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 KEY `object_id` (`object_id`)
) ENGINE=InnoDB");

	$dbxlink->query	("
CREATE TABLE IF NOT EXISTS `TasksItem`(
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `definition_id` int(10) unsigned NOT NULL,
 `object_id` int(10) unsigned NOT NULL,
 `user_name` varchar(24) NOT NULL DEFAULT '',
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
		$task_id = genericAssertion ('graph_id', 'natural');
		if (! array_key_exists ($task_id, getTasksItemsForObject (getBypassValue())))
			throw new InvalidRequestArgException ('graph_id', $task_id);
		proxyTasksRequest (genericAssertion ('definition_id', 'natural'), $task_id);
	}
	return TRUE;
}
*/

function plugin_tasks_resetObject ($object_id)
{
	usePreparedDeleteBlade ('TasksItem', array ('object_id' => $object_id));
}

function plugin_tasks_resetUIConfig ()
{
	setConfigVar ('TASKS_LISTSRC', 'false');
//	setConfigVar ('CACTI_RRA_ID', '1');
}

function getTasksFrequencies() {
	return array(86400 => 'daily', 604800 => 'weekly');
}

function getTasksObjects() {
	$result = usePreparedSelectBlade
	(
		'SELECT 0 as `id`, \'none\' as `name`
		UNION
		(SELECT `id`, `name` FROM `Object` ORDER BY `name`)'
	);
	return reduceSubarraysToColumn(reindexById ($result->fetchAll (PDO::FETCH_ASSOC), 'id'), 'name');
}

function getTasksItemsOutstanding ()
{
	$result = usePreparedSelectBlade
	(
		'SELECT TI.`id`, `definition_id`, `object_id`, O.`name` as `object_name`, ' .
		'TI.`user_name` AS completed_by, TI.name, TI.`notes`, ' .
		'TI.`description`, TI.`completed`, TI.`completed_time`, TI.`created_time` ' .
		'FROM `TasksItem` AS TI ' .
		'LEFT JOIN `Object` O ON O.id = TI.`object_id` ' .
		'WHERE completed = \'no\' ORDER BY `object_id`, `created_time` '
	);
	return reindexById ($result->fetchAll (PDO::FETCH_ASSOC), 'id');
}

function getTasksItemsForObject ($object_id, $include_completed = false)
{
	$result = usePreparedSelectBlade
	(
		'SELECT TI.`id`, `definition_id`, `object_id`, O.`name` as `object_name`, ' .
		'TI.`user_name` AS completed_by, TI.name, TI.`notes`, ' .
		'TI.`description`, TI.`completed`, TI.`completed_time`, TI.`created_time` ' .
		'FROM `TasksItem` AS TI ' .
		'LEFT JOIN `Object` O ON O.id = TI.`object_id` ' .
		'WHERE `object_id` = ? ' . ($include_completed ? '' : ' AND TI.`completed` = \'no\' ') .
		'ORDER BY `completed`, `completed_time`, `created_time`, `id`',
		array ($object_id)
	);
	return reindexById ($result->fetchAll (PDO::FETCH_ASSOC), 'id');
}

function getTasksDefinitions ()
{
	$result = usePreparedSelectBlade
	(
		'SELECT TD.`id`, TD.`name`, TD.`description`, TD.`frequency`, TD.`enabled`, ' .
		'TD.`processed_time`, TD.`created_time`, TD.`object_id`, ' .
		'O.`name` AS `object_name`, COUNT(TI.`id`) AS num_items ' .
		'FROM `TasksDefinition` AS TD ' .
		'LEFT JOIN `TasksItem` AS TI ON TD.`id` = TI.`definition_id` ' .
		'LEFT JOIN `Object` AS O ON O.`id` = TD.`object_id`' .
		'GROUP BY id'
	);
	return reindexById ($result->fetchAll (PDO::FETCH_ASSOC));
}

function renderTasks ()
{
	$columns = array
	(
		array ('th_text' => 'name',         'row_key' => 'name'),
		array ('th_text' => 'description',  'row_key' => 'description'),
		array ('th_text' => 'object',       'row_key' => 'object_name'),
		array ('th_text' => 'created',      'row_key' => 'created_time'),
		array ('th_text' => 'notes',        'row_key' => 'notes'),
	);
	$tasks = getTasksItemsOutstanding ();
	startPortlet ('Tasks outstanding (' . count ($tasks) . ')');
	renderTableViewer ($columns, $tasks);
	finishPortlet ();
}

function renderTasksConfig ()
{
	$columns = array
	(
		array ('th_text' => 'name',        'row_key' => 'name'),
		array ('th_text' => 'description', 'row_key' => 'description'),
		array ('th_text' => 'enabled',     'row_key' => 'enabled'),
		array ('th_text' => 'freqency',    'row_key' => 'frequency'),
		array ('th_text' => 'object',      'row_key' => 'object_name'),
		array ('th_text' => 'task(s)',     'row_key' => 'num_items', 'td_class' => 'tdright'),
		array ('th_text' => 'processed',   'row_key' => 'processed_time'),
		array ('th_text' => 'created',     'row_key' => 'created_time'),
	);
	$definitions = getTasksDefinitions ();
	startPortlet ('Tasks definitions (' . count ($definitions) . ')');
	renderTableViewer ($columns, $definitions);
	finishPortlet ();
}

function renderTasksDefinitionsEditor ()
{
	function printNewItemTR ()
	{
		printOpFormIntro ('add');
		echo '<tr>' .
			'<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>' .
			'<td><input type=text size=24 name=name></td>' .
			'<td><input type=text size=48 name=description></td>' .
			'<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'enabled', 'id' => 'enabled'), 'yes') . '</td>' .
			'<td>' . getSelect (getTasksFrequencies(), array('name' => 'frequency', id => 'frequency'), 86400, FALSE) . '</td>' .
			'<td>' . getSelect (getTasksObjects(), array('name' => 'object_id', 'id' => 'object_id'), 0, FALSE) . '</td>' .
			'<td>&nbsp;</td>' .
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
		'<th>frequency</th>' .
		'<th>object</th>' .
		'<th>item(s)</th>' .
		'<th>&nbsp;</th>' .
		'<th>&nbsp;</th>' .
		'</tr>';
	if (getConfigVar ('ADDNEW_AT_TOP') == 'yes')
		printNewItemTR ();
	foreach (getTasksDefinitions () as $definition)
	{
		printOpFormIntro ('upd', array ('id' => $definition['id']));
		echo '<tr><td>';
		if ($definition['num_items'])
			printImageHREF ('nodestroy', 'cannot delete, tasks exist');
		else
			echo getOpLink (array ('op' => 'del', 'id' => $definition['id']), '', 'destroy', 'delete this definition');
		echo '<td><input type=text size=24 name=name value="' . htmlspecialchars ($definition['name'], ENT_QUOTES, 'UTF-8') . '"></td>';
		echo '<td><input type=text size=48 name=description value="' . htmlspecialchars ($definition['description'], ENT_QUOTES, 'UTF-8') . '"></td>';
		echo '<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'enabled', 'id' => 'enabled'), $definition['enabled']) . '</td>';
		echo '<td>' . getSelect (getTasksFrequencies(), array('name' => 'frequency', id => 'frequency'), $definition['frequency'], FALSE) . '</td>';
		echo '<td>' . getSelect (getTasksObjects(), array('name' => 'object_id', 'id' => 'object_id'), $definition['object_id'], FALSE) . '</td>';
		echo "<td class=tdright>${definition['num_items']}</td>";
		echo '<td>' . getImageHREF ('save', 'update this definition', TRUE) . '</td>';
		echo '</tr></form>';
	}
	if (getConfigVar ('ADDNEW_AT_TOP') != 'yes')
		printNewItemTR ();
	echo '</table>';
}

function renderObjectTasksItems ($object_id)
{
	$isDefault = empty($_REQUEST['tab'] || $_REQUEST['tab'] == 'default');

	if ($isDefault) {
		startPortlet ('Tasks History');
	} else {
		startPortlet ('Tasks Oustanding');
	}
	echo '<table cellspacing=0 cellpadding=5 align=center class=widetable>';
	echo '<tr>' .
		'<th>&nbsp;</th>' .
		'<th>name</th>' .
		'<th>description</th>' .
		'<th>completed</th>' .
		'<th>completed time</th>' .
		'<th>completed user</th>' .
		'<th>notes</th>' .
		'<th>&nbsp;</th>' .
		'</tr>';
	foreach (getTasksItemsForObject ($object_id, $isDefault) as $task_id => $task)
	{
		printOpFormIntro ('upd', array ('id' => $task['id']));
		echo '<tr><td>';
		echo '<td>' . htmlspecialchars ($task['name'], ENT_QUOTES, 'UTF-8') . '</td>';
		echo '<td>' . htmlspecialchars ($task['description'], ENT_QUOTES, 'UTF-8') . '</td>';
		if ($task['completed'] == 'no' && !$isDefault) {
			echo '<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'completed', 'id' => 'completed'), $task['completed']) . '</td>';
			echo '<td><i>incomplete</i></td>';
			echo '<td>&nbsp;</td>';
			echo '<td><input type=textarea size=48 name=notes value="' . htmlspecialchars ($task['notes'], ENT_QUOTES, 'UTF-8') . '"></td>';
		} else {
			echo '<td>' . htmlspecialchars ($task['completed'], ENT_QUOTES, 'UTF-8') . '</td>';
			echo '<td>' . htmlspecialchars ($task['completed_time'], ENT_QUOTES, 'UTF-8') . '</td>';
			echo '<td>' . htmlspecialchars ($task['completed_by'], ENT_QUOTES, 'UTF-8') . '</td>';
			echo '<td>' . htmlspecialchars ($task['notes'], ENT_QUOTES, 'UTF-8') . '</td>';
		}
		if ($task['completed'] == 'yes' || $isDefault) {
			echo '<td>&nbsp;</td>';
		} else {
			echo '<td>' . getImageHREF ('save', 'update this definition', TRUE) . '</td>';
		}
		echo '</tr></form>';
	}
	echo "</table>\n";
	finishPortlet ();
}

function triggerTasksItems ()
{
	if (! count (getTasksDefinitions ()))
		return '';
	if
	(
		count (getTasksItemsForObject (getBypassValue (), true)) or
		considerConfiguredConstraint (spotEntity ('object', getBypassValue ()), 'TASKS_LISTSRC')
	)
		return 'std';
	return '';
}

function updObjectTasksItem () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	updateObjectTasksItem
	(
		genericAssertion ('id', 'uint'),
		genericAssertion ('completed', 'enum/yesno'),
		genericAssertion ('notes', 'string0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}

function updateObjectTasksItem($id, $completed, $notes) {

	global $remote_username;

	$result = usePreparedSelectBlade ("SELECT object_id, completed FROM TasksItem WHERE id = ?", array($id));
	$row = $result->fetch (PDO::FETCH_ASSOC);
	if ($row) {

		if ($row['completed'] == 'yes') {
			throw new RTDatabaseError('Cannot update completed record, already completed');
		}

		$fields = array( 'notes' => $notes );

		if ($completed == 'yes') {
			$fields['completed'] = $completed;
			$fields['completed_time'] = date('Y-m-d H:i:s');
			$fields['user_name'] = $remote_username;
		}

		usePreparedUpdateBlade
		(
			'TasksItem',
			$fields,
			array('id' => $id)
		);
		recordObjectHistory($row['object_id']);
	}
}

