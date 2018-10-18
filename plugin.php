<?php

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

	/* Tasks Top Tab */
	$page['tasks']['title'] = 'Tasks';
	$page['tasks']['default'] = 'default';

	$tab['tasks']['default'] = 'Outstanding';
	$tab['tasks']['history'] = 'History';
	$tab['tasks']['definitions'] = 'Definitions';

	registerTabHandler ('tasks', 'default', 'renderTasksItems');
	registerTabHandler ('tasks', 'history', 'renderTasksItems');
	registerTabHandler ('tasks', 'definitions', 'renderTasksDefinitionsEditor');
	registerOpHandler ('tasks', 'default', 'upd', 'updTasksItem');
	registerOpHandler ('tasks', 'definitions', 'add', 'addTasksDefinition');
	registerOpHandler ('tasks', 'definitions', 'upd', 'updTasksDefinition');

	/* Tasks Definitions in Config */
	$page['tasksconfig']['title'] = 'Task Definitions';
	$page['tasksconfig']['parent'] = 'config';

	$tab['tasksconfig']['default'] = 'View';
	$tab['tasksconfig']['definitions'] = 'Manage definitions';

	registerTabHandler ('tasksconfig', 'default', 'renderTasksDefinitions');
	registerTabHandler ('tasksconfig', 'definitions', 'renderTasksDefinitionsEditor');

	registerOpHandler ('tasksconfig', 'definitions', 'add', 'addTasksDefinition');
	registerOpHandler ('tasksconfig', 'definitions', 'upd', 'updTasksDefinition');
	//registerOpHandler ('tasksconfig', 'definitions', 'del', 'delTasksDefinition');

	/* Tasks in Object */
	$tab['object']['tasksitem'] = 'Tasks';
	$trigger['object']['tasksitem'] = 'triggerTasksItems';

	registerTabHandler ('object', 'tasksitem', 'renderTasksItems');
	registerTabHandler ('object', 'default', 'renderTasksItems');

	registerOpHandler ('object', 'tasksitem', 'add', 'addTasksItem');
	registerOpHandler ('object', 'tasksitem', 'upd', 'updTasksItem');
	//registerOpHandler ('object', 'tasksitem', 'del', 'delTasksItem');

	$interface_requires['tasksconfig-*'] = 'interface-config.php';
	$interface_requires['tasksitem-*'] = 'interface-config.php';
	$interface_requires['tasks-*'] = 'interface-config.php';

	registerHook ('resetObject_hook', 'plugin_tasks_resetObject');
	registerHook ('resetUIConfig_hook', 'plugin_tasks_resetUIConfig');

	addCSS("
	@import url('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
	@import url('https://npmcdn.com/flatpickr/dist/themes/material_blue.css');
	", true);

	global $plugin_tasks_fkeys;
	$plugin_tasks_fkeys = array (
		array ('fkey_name' => 'TasksItem-FK-object_id', 'table_name' => 'TasksItem'),
		array ('fkey_name' => 'TasksDefinition-FK-object_id', 'table_name' => 'TasksDefinition'),
		array ('fkey_name' => 'TasksItem-FK-definition_id', 'table_name' => 'TasksItem'),
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
CREATE TABLE IF NOT EXISTS `TasksDefinition` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` char(64) DEFAULT NULL,
 `description` char(255) DEFAULT NULL,
 `frequency` char(40) NOT NULL DEFAULT 'tomorrow midnight',
 `mode` enum('due', 'schedule') NOT NULL DEFAULT 'due',
 `enabled` enum('yes','no') NOT NULL DEFAULT 'no',
 `object_id` int(10) unsigned NOT NULL,
 `start_time` timestamp NULL,
 `processed_time` timestamp NULL,
 `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 KEY `object_id` (`object_id`),
 INDEX `mode` (`mode`)
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

function getTasksDiffValue($date1, $date2) {
	return $date2->diff($date1)->format('%a');;
}

function getTasksNextDue($freq, $date = null) {
	$date_orig = $date != null ? $date : new DateTime();
	$date_freq = clone($date_orig);

	try {
		$freq = parseTasksFrequency($freq);
		if (!empty($freq['data'])) {
			if ($freq['data'][0] == 'P') {
				$freq = new DateInterval($freq['data']);
				$date_freq = $date_freq->add($freq);
			} else {
				$date_freq = $date_freq->modify($freq['data']);
			}
		}
	} catch (Exception $e) {
	}

	if ($date_orig->getTimestamp() == $date_freq->getTimestamp()) {
		throw new RTDatabaseError('unable to get next due date');
	}
	return $date_freq;
}

function getTasksModes() {
	return array('due' => 'next due', 'schedule' => 'scheduled');
}

function getTasksDiffString($interval) {
	$doPlural = function($nb,$str){return $nb>1?$str.'s':$str;}; // adds plurals

	$format = array();
	if($interval->y !== 0) {
		$format[] = "%y&nbsp;".$doPlural($interval->y, "year");
	}

	if($interval->m !== 0) {
        	$format[] = "%m&nbsp;".$doPlural($interval->m, "month");
	}

	if($interval->d !== 0) {
		$format[] = "%d&nbsp;".$doPlural($interval->d, "day"); 
	}

	if($interval->h !== 0) {
		$format[] = "%h&nbsp;".$doPlural($interval->h, "hour");
	}

	if($interval->i !== 0) {
		$format[] = "%i&nbsp;".$doPlural($interval->i, "minute");
	}

	if($interval->s !== 0) {
		if(!count($format)) {
			return "&lt;&nbsp;a&nbsp;minute";
		} else {
			$format[] = "%s&nbsp;".$doPlural($interval->s, "second");
		}
	}
	$format = implode(',&nbsp;', array_slice($format, 0, 2));
	$overdue = array('0' => 'Due in', '1' => 'Overdue by');
	return $overdue[$interval->invert] . ' ' . $interval->format($format);
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

function getTasksItems ($object_id, $include_completed = false)
{
	$tasksWhere = ($object_id > 0) ?
		'WHERE (TI.`object_id` = ?) ' :
		'WHERE (TI.`object_id` >= 0 OR TI.`object_id` IS NULL) ';

	if (!$include_completed) $tasksWhere .= 'AND TI.`completed` = \'no\' ';

	$mainSQL = 'SELECT TI.`id`, `definition_id`, TI.`object_id`, O.`name` as `object_name`, ' .
		'TI.`user_name` AS completed_by, TI.`name`, TI.`mode`, TI.`notes`, ' .
		'TI.`description`, TI.`completed`, TI.`completed_time`, TI.`created_time`, ' .
		'TD.`frequency` ' .
		'FROM `TasksItem` AS TI ' .
		'INNER JOIN `TasksDefinition` AS TD ON TD.`id` = TI.`definition_id` ' .
		'    AND (TD.`enabled` = "yes" OR TI.`completed` = "no") ' .
		'LEFT JOIN `Object` O ON O.id = TI.`object_id` ';

	$result = usePreparedSelectBlade
	(
		$mainSQL .
		$tasksWhere .
		'ORDER BY `completed` DESC, `completed_time` DESC, `created_time` ASC, `id`',
		array ($object_id)
	);
	return $result->fetchAll (PDO::FETCH_ASSOC);
}

function parseTasksFrequency($frequency) {
	$label = '';
	$data  = '';

	if (preg_match('~(?<label>[^()]+)(\((?<data>.*)\))~', $frequency, $matches)) {
		if (isset($matches['data'])) {
			$label = trim($matches['label']);
			$data = trim($matches['data']);
		}
	}

	if (empty($data)) {
		$data = $frequency;
	}

	if (empty($label)) {
		$label = 'unlabelled';
	}
	return array('label' => $label, 'data' => $data);
}

function getTasksFrequencyList() {
	return "<datalist id='frequencyList'>
			<option value='daily (tomorrow)'>
			<option value='weekly (+1 week midnight)'>
			<option value='monthly (+1 month midnight)'>
			<option value='quarterly (+3 months midnight)'>
			<option value='semi-annual (+6 months midnight)'>
			<option value='annual (+1 year midnight)'>
			<option value='first of month (first day of next month)'>
			<option value='last of month (last day of next month)'>
			<option value='first tuesday (first tuesday of next month)'>
			<option value='last tuesday (last tuesday of next month)'>
			<option value='next monday'>
			<option value='next tuesday'>
			<option value='next wednesday'>
			<option value='next thursday'>
			<option value='next friday'>
			<option value='next saturday'>
			<option value='next sunday'>
	</datalist>";
}

function getTasksDefinitions ()
{
	$result = usePreparedSelectBlade
	(
		'SELECT TD.`id`, TD.`name`, TD.`description`, TD.`frequency`, TD.`enabled`, ' .
		'TD.`mode`, TD.`processed_time`, TD.`created_time`,TD.`start_time`, ' .
		'TD.`object_id`, O.`name` AS `object_name`, COUNT(TI.`id`) AS num_items ' .
		'FROM `TasksDefinition` AS TD ' .
		'LEFT JOIN `TasksItem` AS TI ON TD.`id` = TI.`definition_id` ' .
		'LEFT JOIN `Object` AS O ON O.`id` = TD.`object_id`' .
		'GROUP BY id'
	);
	return reindexById ($result->fetchAll (PDO::FETCH_ASSOC));
}

function renderTasksDefinitions ()
{
	$columns = array
	(
		array ('th_text' => 'name',        'row_key' => 'name'),
		array ('th_text' => 'description', 'row_key' => 'description'),
		array ('th_text' => 'enabled',     'row_key' => 'enabled'),
		array ('th_text' => 'mode',        'row_key' => 'mode'),
		array ('th_text' => 'start_time',  'row_key' => 'start_time'),
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
	echo getTasksFrequencyList();

	echo "<script>$(function() {
		$.getScript('https://cdn.jsdelivr.net/npm/flatpickr', function() {
			$('.tasks-datetime').flatpickr({
				enableTime: true,
				dateFormat: 'Y-m-d H:i:S',
				altInput: true,
				altFormat: 'M j, Y H:i:S',
				time_24hr: true,
			});
		});
	});</script>";

	function printNewItemTR ()
	{
		printOpFormIntro ('add');
		echo '<tr>' .
			'<td>' . getImageHREF ('create', 'add a new definition', TRUE) . '</td>' .
			'<td><input type=text size=24 name=name></td>' .
			'<td><input type=text size=48 name=description></td>' .
			'<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'enabled', 'id' => 'enabled'), 'yes') . '</td>' .
			'<td>' . getSelect (getTasksModes(), array ('name' => 'mode', 'id' => 'mode'), 'due') . '</td>' .
			'<td><input type=text size=24 name=start_time class="tasks-datetime"></td>' .
			'<td><input type=text size=24 name=frequency list="frequencyList"></td>' .
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
		'<th>mode</th>' .
		'<th>start_time</th>' .
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
		echo '<td>' . getSelect (getTasksModes(), array ('name' => 'mode', 'id' => 'mode'), $definition['mode']) . '</td>';
		if ($definition['num_items'])
			echo "<td>{$definition['start_time']}</td>";
		else
			echo "<td><input type=text size=24 name=start_time class='tasks-datetime' value='{$definition['start_time']}'></td>";
		echo '<td><input type=text size=24 name=frequency list="frequencyList" value="' . htmlspecialchars ($definition['frequency'], ENT_QUOTES, 'UTF-8') . '"></td>';
		echo '<td>' . getSelect (getTasksObjects(), array('name' => 'object_id', 'id' => 'object_id'), $definition['object_id'], FALSE) . '</td>';
		echo "<td class=tdright>${definition['num_items']}</td>";
		echo '<td>' . getImageHREF ('save', 'update this definition', TRUE) . '</td>';
		echo '</tr></form>';
	}
	if (getConfigVar ('ADDNEW_AT_TOP') != 'yes')
		printNewItemTR ();
	echo '</table>';
}

function renderTasksItems ($object_id)
{
	if (!isset($object_id)) {
		if (isset($_REQUEST['object_id'])) {
			$object_id = genericAssertion('object_id', 'uint');
		} else {
			$object_id = 0;
		}
	}

	$isTasksPage = $_REQUEST['page'] == 'tasks';
	if ($isTasksPage) {
		$isHistoryTab = isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'history';
	} else {
		$isHistoryTab = empty($_REQUEST['tab']) || $_REQUEST['tab'] == 'default';
	}

	if ($isHistoryTab) {
		startPortlet ('Tasks History');
	} else {
		startPortlet ('Tasks Oustanding');
	}

	echo '<table cellspacing=0 cellpadding=5 align=center class=widetable>';
	echo '<tr><th>&nbsp;</th>';

	if ($isTasksPage) {
		echo '<th>object</th>';
	}

	echo '<th>name</th>' .
		'<th>description</th>' .
		'<th>mode</th>' .
		'<th>created/due time</th>' .
		'<th>completed</th>' .
		'<th>completed time</th>' .
		'<th>completed user</th>' .
		'<th>notes</th>' .
		'<th>&nbsp;</th>' .
		'</tr>';

	$now = new DateTime();
	foreach (getTasksItems ($object_id, $isHistoryTab) as $task_id => $task)
	{
		$color = 'transparent';
		$incomplete = 'incomplete';

		if ($task['completed'] == 'no' && $task['mode'] == 'due') {
			$created = new DateTime($task['created_time']);
			$diff  = $now->diff($created);
			$incomplete = getTasksDiffString($diff);

			if ($created <= $now) {
				$freq  = $task['frequency'];
				$next  = clone $created;
				$count = 0;

				while ($count < 3 && $next < $now) {
					$next = getTasksNextDue($freq, $next);
					$count++;
				}

				if ($count > 2) {
					$color = 'red';
				} else if ($count > 1) {
					$color = 'orange';
				} else {
					$color = 'yellow';
				}
			}
		}

		printOpFormIntro ('upd', array ('id' => $task['id']));
		echo '<tr style="background: ' . $color . ';"><td>';
		if ($isTasksPage) {
			if (empty($task['object_name'])) {
				echo '<td>&nbsp;</td>';
			} else {
				echo '<td>' .  mkA (stringForLabel ($task['object_name']), 'object', $task['object_id']) . '</td>';
			}
		}


		echo '<td>' . mkA (stringForLabel ($task['name']), 'tasks', $task['definition_id'], 'definitions') . '</td>';
		echo '<td>' . htmlspecialchars ($task['description'], ENT_QUOTES, 'UTF-8') . '</td>';
		echo '<td>' . htmlspecialchars ($task['mode'], ENT_QUOTES, 'UTF-8') . '</td>';
		echo '<td>' . htmlspecialchars ($task['created_time'], ENT_QUOTES, 'UTF-8') . '</td>';
		if ($task['completed'] == 'no' && !$isHistoryTab) {
			echo '<td>' . getSelect (array ('yes' => 'yes', 'no' => 'no'), array ('name' => 'completed', 'id' => 'completed'), $task['completed']) . '</td>';
			echo '<td><i>' . $incomplete . '</i></td>';
			echo '<td>&nbsp;</td>';
			echo '<td><input type=textarea size=48 name=notes value="' . htmlspecialchars ($task['notes'], ENT_QUOTES, 'UTF-8') . '"></td>';
		} else {
			echo '<td>' . htmlspecialchars ($task['completed'], ENT_QUOTES, 'UTF-8') . '</td>';
			if ($task['completed'] == 'no') {
				echo '<td colspan="2">' . $incomplete . '</td>';
			} else {
				echo '<td>' . htmlspecialchars ($task['completed_time'], ENT_QUOTES, 'UTF-8') . '</td>';
				echo '<td>' . htmlspecialchars ($task['completed_by'], ENT_QUOTES, 'UTF-8') . '</td>';
			}
			echo '<td>' . htmlspecialchars ($task['notes'], ENT_QUOTES, 'UTF-8') . '</td>';
		}
		if ($task['completed'] == 'yes' || $isHistoryTab) {
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
/*	if (! count (getTasksDefinitions ()))
		return '';
	if
	(
		count (getTasksItems (getBypassValue (), true)) //or
		considerConfiguredConstraint (spotEntity ('object', getBypassValue ()), 'TASKS_LISTSRC')
	)
*/		return 'std';
	return '';
}

function addTasksDefinition () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	$id = insertTasksDefinition
	(
		plugin_tasks_assert ('name', 'string'),
		plugin_tasks_assert ('description', 'string'),
		plugin_tasks_assert ('enabled', 'enum/yesno'),
		plugin_tasks_assert ('frequency', 'frequency'),
		plugin_tasks_assert ('start_time', 'datetime'),
		plugin_tasks_assert ('mode', 'enum/mode'),
		plugin_tasks_assert ('object_id', 'uint0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}

function insertTasksDefinition($name, $description, $enabled, $frequency, $start_time, $mode, $object_id) {
	$fields = array(
		'name' => $name,
		'description' => $description,
		'enabled' => $enabled,
		'frequency' => $frequency,
		'start_time' => $start_time,
		'mode' => $mode,
		'object_id' => $object_id,
	);

	$id = usePreparedInsertBlade
	(
		'TasksDefinition',
		$fields
	);

	ensureTasksDefinitionNextDue ($id);
	return $id;
}

function updTasksDefinition () {

	setFuncMessages (__FUNCTION__, array ('OK' => 51));
	updateTasksDefinition
	(
		plugin_tasks_assert ('id', 'uint'),
		plugin_tasks_assert ('name', 'string'),
		plugin_tasks_assert ('description', 'string'),
		plugin_tasks_assert ('enabled', 'enum/yesno'),
		plugin_tasks_assert ('frequency', 'frequency'),
		plugin_tasks_assert ('mode', 'enum/mode'),
		plugin_tasks_assert ('object_id', 'uint0')
	);
	showFuncMessage (__FUNCTION__, 'OK');
}

function updateTasksDefinition($id, $name, $description, $enabled, $frequency, $mode, $object_id) {
	$fields = array(
		'id' => $id,
		'name' => $name,
		'description' => $description,
		'enabled' => $enabled,
		'mode' => $mode,
		'frequency' => $frequency,
		'object_id' => $object_id,
	);

	usePreparedUpdateBlade
	(
		'TasksDefinition',
		$fields,
		array('id' => $id)
	);

	disableTasksItemsOutstanding ($id);
	ensureTasksDefinitionNextDue ($id);
}

function updateTasksDefinitionProcessedTime ($id, $date) {
	$fields = array(
		'id' => $id,
		'processed_time' => $date
	);

	usePreparedUpdateBlade
	(
		'TasksDefinition',
		$fields,
		array('id' => $id)
	);
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

function insertTasksItem($definition_id, $mode, $name, $description, $object_id, $time = null) {
	if ($mode == 'schedule') {
	}

	if ($time == null) {
		$time = new DateTime();
	}

	$fields = array(
		'definition_id' => $definition_id,
		'name'          => $name,
		'mode'          => $mode,
		'description'   => $description,
		'object_id'     => $object_id,
		'created_time'  => $time,
	);

	usePreparedInsertBlade
	(
		'TasksItem',
		$fields
	);
}

function updateTasksItem($id, $completed, $notes, $user = '') {

	global $remote_username;

	$result = usePreparedSelectBlade ("
		SELECT TI.`id`, TI.`object_id`, TI.`created_time`, TI.`completed`,
		TI.`definition_id`, TD.`enabled`, TD.`frequency`, TD.`mode`
		FROM TasksItem TI
		INNER JOIN TasksDefinition TD ON TD.`id` = TI.`definition_id`
		WHERE TI.`id` = ?", array($id));

	$row = $result->fetch (PDO::FETCH_ASSOC);
	if ($row) {

		if ($row['completed'] == 'yes') {
			throw new RTDatabaseError('Cannot update an already completed record: ' . $id);
		}

		$fields = array( 'notes' => $notes );

		if ($completed == 'yes') {
			$fields['completed'] = $completed;
			$fields['completed_time'] = date('Y-m-d H:i:s');
			$fields['user_name'] = empty($user) ? $remote_username : $user;

			/*
			if (empty($fields['notes'])) {
				throw new RTDatabaseError('Cannot complete, no notes added');
			}
			*/
		}

		usePreparedUpdateBlade
		(
			'TasksItem',
			$fields,
			array('id' => $id)
		);

		if ($row['object_id'] > 0) {
			recordObjectHistory($row['object_id']);
		}

		if ($row['mode'] == 'due') {
			ensureTasksDefinitionNextDue($row['definition_id']);
		}
	}
}

function ensureTasksDefinitionNextDue($id) {
	$definition_select = usePreparedSelectBlade ("SELECT * FROM TasksDefinition WHERE id = ?", array($id));
	$definition = $definition_select->fetch (PDO::FETCH_ASSOC);
	if ($definition && $definition['enabled'] == 'yes' && $definition['mode'] == 'due') {
		$last_select = usePreparedSelectBlade ("SELECT id, completed, created_time FROM TasksItem WHERE definition_id = ? ORDER BY created_time DESC, id DESC LIMIT 1", array($id));
		$last = $last_select->fetch (PDO::FETCH_ASSOC);

		if (!isset($last['completed']) || $last['completed'] == 'yes') {
			$base = $definition['start_time'] ? $definition['start_time'] : $definition['created_time'];
			if (isset($last['created_time'])) {
				$base = $last['created_time'];
				$next = getTasksNextDue($definition['frequency'], new DateTime($base));
			} else {
				$next = new DateTime($base);
			}

			/*
			echo "PT: " . $definition['processed_time'] . "\n";
			echo "CR: " . $definition['created_time'] . "\n";
			echo "CT: " . $last['created_time'] . "\n";
			echo "NT: " . $next->format('Y-m-d H:i:s') . "\n";
			*/

			insertTasksItem($definition['id'], $definition['mode'], $definition['name'], $definition['description'], $definition['object_id'], $next->format('Y-m-d H:i:s'));
		}
	}
}
function disableTasksItemsOutstanding($id) {
	$definition_select = usePreparedSelectBlade ("SELECT * FROM TasksDefinition WHERE id = ?", array($id));
	$definition = $definition_select->fetch (PDO::FETCH_ASSOC);
	if ($definition && $definition['enabled'] == 'no') {
		$item_select = usePreparedSelectBlade ("SELECT id FROM TasksItem WHERE definition_id = ? and completed = 'no'", array($id));
		$item = $item_select->fetch (PDO::FETCH_ASSOC);
		if (isset($item['id'])) {
			updateTasksItem ($item['id'], 'yes', 'Auto completed by System (definition disabled)', 'system');
		}
	}
}
