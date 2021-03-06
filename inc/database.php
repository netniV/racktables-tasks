<?php

function getTasksObjectEntities() {
	$result = usePreparedSelectBlade
	(
		'SELECT `id`, `name` FROM `Object` ORDER BY `name`'
	);

	$ret = reduceSubarraysToColumn(reindexById ($result->fetchAll (PDO::FETCH_ASSOC), 'id'), 'name');
	uasort($ret, 'strcasecmp');
	$array = array(0 => 'none');
	foreach ($ret as $key=>$item) {
		$array[$key] = $item;
	}
	return $array;
}

/*** TASKS FREQUENCIES ***/

function getTasksFrequencyEntities () {
	$result = usePreparedSelectBlade
	(
		'SELECT TF.`id`, TF.`name`, TF.`format` ' .
		'FROM `TasksFrequency` AS TF ' .
		'ORDER BY `name`'
	);

	$ret = reduceSubarraysToColumn (reindexById ($result->fetchAll (PDO::FETCH_ASSOC), 'id'), 'name');
	asort($ret);
	return $ret;
}

function getTasksFrequencies ($frequency_id = 0) {
	$where = '';
	$params = array();

	if ($frequency_id) {
		$where = 'WHERE TF.`id` = ? ';
		$params[] = $frequency_id;
	}


	$result = usePreparedSelectBlade
	(
		'SELECT TF.`id`, TF.`name`, TF.`format`, ' .
		'COUNT(TD.`id`) AS num_items ' .
		'FROM `TasksFrequency` AS TF ' .
		'LEFT JOIN `TasksDefinition` AS TD ON TF.`id` = TD.`frequency_id` ' .
		$where .
		'GROUP BY `id`, `name`, `format` ' .
		'ORDER BY `name` ',
		$params
	);

	return reindexById ($result->fetchAll (PDO::FETCH_ASSOC));
}

function insertTasksFrequency ($name, $format) {
	$fields = array(
		'name' => $name,
		'format' => $format,
	);

	$id = usePreparedInsertBlade
	(
		'TasksFrequency',
		$fields
	);

	return $id;
}

function updateTasksFrequency ($id, $name, $format) {
	$fields = array(
		'id' => $id,
		'name' => $name,
		'format' => $format,
	);

	usePreparedUpdateBlade
	(
		'TasksFrequency',
		$fields,
		array('id' => $id)
	);
}

/*** TASKS DEFINITIONS ***/

function getTasksDefinitions ($definition_id = 0)
{
	$where = '';
	$params = array();

	if ($definition_id) {
		$where = 'WHERE TD.`id` = ? ';
		$params[] = $definition_id;
	}

	$result = usePreparedSelectBlade
	(
		'SELECT TD.`id`, TD.`name`, TD.`description`, TD.`department`, TD.`details`, TD.`enabled`, TD.`repeat`, ' .
		'TD.`mode`, TD.`processed_time`, TD.`created_time`,TD.`start_time`, ' .
		'TD.`object_id`, O.`name` AS `object_name`, COUNT(TI.`id`) AS num_items, ' .
		'TF.`name` AS frequency_name, TF.`id` AS frequency_id, TF.`format` AS frequency_format ' .
		'FROM `TasksDefinition` AS TD ' .
		'JOIN `TasksFrequency` AS TF ON TF.`id` = TD.`frequency_id` ' .
		'LEFT JOIN `TasksItem` AS TI ON TD.`id` = TI.`definition_id` ' .
		'LEFT JOIN `Object` AS O ON O.`id` = TD.`object_id` ' .
		$where .
		'GROUP BY id',
		$params
	);
	return reindexById ($result->fetchAll (PDO::FETCH_ASSOC));
}

function insertTasksDefinition ($name, $description, $enabled, $frequency_id, $start_time, $mode, $object_id, $details, $repeat, $department) {
	$fields = array(
		'name' => $name,
		'description' => $description,
		'enabled' => $enabled,
		'details' => $details,
		'frequency_id' => $frequency_id,
		'start_time' => $start_time,
		'mode' => $mode,
		'object_id' => $object_id,
		'repeat' => $repeat,
		'department' => $department,
	);

	if (usePreparedInsertBlade ('TasksDefinition',$fields)) {
		$id = lastInsertID();
		ensureTasksDefinitionNextDue ($id);
	}
	return $id;
}

function updateTasksDefinition ($id, $name, $description, $enabled, $frequency_id, $mode, $object_id, $details, $repeat, $department) {
	$fields = array(
		'id' => $id,
		'name' => $name,
		'description' => $description,
		'enabled' => $enabled,
		'details' => $details,
		'mode' => $mode,
		'frequency_id' => $frequency_id,
		'object_id' => $object_id,
		'repeat' => $repeat,
		'department' => $department,
	);

	usePreparedUpdateBlade
	(
		'TasksDefinition',
		$fields,
		array('id' => $id)
	);

	updateTasksItemsFromDefinition ($id);
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

function ensureTasksDefinitionNextDue ($id) {
	recordTasksDebug('ensureTasksDefinitionNextDue(' . $id . '): started');
	$definition = getTasksDefinitions ($id);
	if ($definition) {
		recordTasksDebug('ensureTasksDefinitionNextDue(' . $id . '): found: ' . json_encode($definition));
		$definition = reset($definition);
	} else {
		recordTasksDebug('ensureTasksDefinitionNextDue(' . $id . '): not found!');
		$definition = array('enabled' => 'missing', 'mode' => 'missing');
	}

	recordTasksDebug('ensureTasksDefinitionNextDue(' . $id . '): Enabled: ' . $definition['enabled'] . ', Mode: ' . $definition['mode'] . ', Repeat: ' . $definition['repeat']);
	if ($definition &&
		$definition['enabled'] == 'yes' &&
		$definition['repeat'] == 'yes' &&
		$definition['mode'] != 'schedule') {
		$last_select = usePreparedSelectBlade ("SELECT id, completed, created_time, completed_time FROM TasksItem WHERE definition_id = ? ORDER BY created_time DESC, id DESC LIMIT 1", array($id));
		$last = $last_select->fetch (PDO::FETCH_ASSOC);

		recordTasksDebug('ensureTasksDefinitionNextDue(' . $id . '): last: ' . var_export($last, true));
		if (!isset($last['completed']) || $last['completed'] == 'yes') {
			$mode = 'unknown';
			$base = null;
			$freq = null;

			if ($definition['mode'] == 'complete') {
				$mode = 'complete';
				$freq = $definition['frequency_format'];
				if (isset($last['completed'])) {
					$mode = 'completed_time';
					$base = $last['completed_time'];
				}
			} elseif ($last['created_time']) {
				$mode = 'created_time';
				$base = $last['created_time'];
				$freq = $definition['frequency_format'];
			}

			if ($base == null) {
				if (isset($definition['start_time'])) {
					$mode = 'created_def';
					$base = $definition['start_time'];
					$freq = $definition['frequency_format'];
				} elseif (isset($definition['created_time'])) {
					$mode = 'created_last';
					$base = $definition['created_time'];
					$freq = $definition['frequency_format'];
				}
			}

			$next = getTasksNextDue($freq, new DateTime($base));
			recordTasksDebug('ensureTasksDefinitionNextDue(' . $id . '): ' . $mode . ': ' .
				$next->format('Y-m-d H:i:s') . ' = getTasksNextDue(' . $freq . ', new DateTime(' .
				$base . '));');

			/*
			echo "PT: " . $definition['processed_time'] . "\n";
			echo "CR: " . $definition['created_time'] . "\n";
			echo "CT: " . $last['created_time'] . "\n";
			echo "NT: " . $next->format('Y-m-d H:i:s') . "\n";
			*/

			recordTasksDebug('ensureTasksDefinitionNextDue(' . $id . '): insertTasksItem(' . $definition['id'] .
				', ' . $definition['mode'] . ', ' . $definition['name'] .', ' . $definition['description'] .
				', ' . $definition['object_id'] . ', ' . $next->format('Y-m-d H:i:s') .
				')');
			insertTasksItem($definition['id'], $definition['mode'], $definition['name'], $definition['description'], $definition['object_id'], $next->format('Y-m-d H:i:s'), $definition['department']);
		}
	} else {
		recordTasksDebug('No definition found for '. $definition['id']);
	}
}

/*** TASKSITEM FUNCTIONS ***/

function getTasksItems ($object_id, $include_completed = '', $task_id = 0, $task_definition_id = 0)
{
	$params = array($object_id);
	$tasksWhere = ($object_id > 0) ?
		'WHERE (TI.`object_id` = ?) ' :
		'WHERE (TI.`object_id` >= ? OR TI.`object_id` IS NULL) ';

	if ($task_id > 0) {
		$tasksWhere .= 'AND (TI.`id` = ?) ';
		$params[] = $task_id;
	}

	if ($task_definition_id > 0) {
		$tasksWhere .= 'AND (TI.`definition_id` = ?) ';
		$params[] = $task_definition_id;
	}

	$definitionWhere = '';
	if ($include_completed !== NULL) {
		if (empty($include_completed)) {
			$include_completed = 'no';
			$definitionWhere = ($include_completed == 'yes') ? '' : '    AND (TD.`enabled` = "yes") ';
		}

		$tasksWhere .= 'AND TI.`completed` = ? ';
		$params[] = $include_completed;
	}

	$mainSQL = 'SELECT DISTINCT TI.`id`, `definition_id`, TI.`object_id`, O.`name` as `object_name`, ' .
		'TI.`user_name` AS completed_by, TI.`name`, TI.`mode`, TI.`notes`, ' .
		'TI.`description`, TI.`department`, TI.`completed`, TI.`completed_time`, TI.`created_time`, ' .
		'TD.`details`, TF.`id` AS `frequency_id`, TF.`name` AS `frequency_name`, ' .
		'TF.`format` AS `frequency_format` ' .
		'FROM `TasksItem` AS TI ' .
		'INNER JOIN `TasksDefinition` AS TD ON TD.`id` = TI.`definition_id` ' .
		$definitionWhere .
		'INNER JOIN `TasksFrequency` AS TF ON TF.`id` = TD.`frequency_id` ' .
		'LEFT JOIN `Object` O ON O.id = TI.`object_id` ';

	//echo "SQL: <pre>" . htmlspecialchars($mainSQL . "\n" . $tasksWhere . "\n" . var_export($params, true)) . "</pre>";
	$result = usePreparedSelectBlade
	(
		$mainSQL .
		$tasksWhere .
		'ORDER BY `completed` DESC, `completed_time` DESC, `created_time` ASC, `id`',
		$params
	);

	return $result->fetchAll (PDO::FETCH_ASSOC);
}

function insertTasksItem ($definition_id, $mode, $name, $description, $object_id, $time = null, $department) {
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
		'department'    => $department,
	);

	usePreparedInsertBlade
	(
		'TasksItem',
		$fields
	);
}

function updateTasksItem ($id, $completed, $notes, $user = '', $time = '') {

	global $remote_username;
	$ret    = false;
	$result = usePreparedSelectBlade ("
		SELECT TI.`id`, TI.`object_id`, TI.`created_time`, TI.`completed`,
		TI.`definition_id`, TD.`enabled`, TD.`mode`, TI.`name`, TI.`description`
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
			$fields['completed_time'] = empty($time) ? date('Y-m-d H:i:s') : $time;
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

		if ($row['mode'] != 'schedule') {
			ensureTasksDefinitionNextDue($row['definition_id']);
		}

		if ($completed == 'yes') {
			if ($row['object_id'] > 0) {
				$log_content = $row['name'] . ' - ' . $row['description'] . ' was completed by ' . $remote_username . '.';
				if (!empty($notes)) {
					$log_content .= '  Notes: ' . $notes;
				}

				usePreparedExecuteBlade
				(
					'INSERT INTO ObjectLog SET object_id=?, user=?, date=NOW(), content=?',
					array ($row['object_id'], $remote_username, $log_content)
				);
			}

			$_PAGE = isset($_REQUEST['page']) ? $_REQUEST['page'] : 'default';
			$_TAB  = isset($_REQUEST['tab'])  ? $_REQUEST['tab'] : 'default';

			if ($_PAGE == 'object') {
				$ret = buildRedirectUrl ('object', $_TAB, array('object_id' => $row['object_id']));
			} else if ($_PAGE == 'tasks' && $_TAB == 'default') {
				$ret = buildRedirectURL ('tasks', 'default');
			} else {
				$ret = buildRedirectURL ('tasksitem', 'default', array('task_item_id' => $id));
			}
		}
	}
	recordTasksDebug("updateTasksItem($id) : returns $ret");
	return $ret;
}

function disableTasksItemsOutstanding ($id, $definition = '') {
	if (empty($definition)) {
		$definition_select = usePreparedSelectBlade ("SELECT * FROM TasksDefinition WHERE id = ?", array($id));
		$definition = $definition_select->fetch (PDO::FETCH_ASSOC);
	}

	if ($definition && $definition['enabled'] == 'no') {
		$item_select = usePreparedSelectBlade ("SELECT id FROM TasksItem WHERE definition_id = ? and completed = 'no'", array($id));
		$item = $item_select->fetch (PDO::FETCH_ASSOC);
		if (isset($item['id'])) {
			updateTasksItem ($item['id'], 'yes', 'Auto completed by System (definition disabled)', 'system');
		}
	}
}

function updateTasksItemsFromDefinition ($id) {
	$definition_select = usePreparedSelectBlade ("SELECT * FROM TasksDefinition WHERE id = ?", array($id));
	$definition = $definition_select->fetch (PDO::FETCH_ASSOC);

	if ($definition['enabled'] == 'yes') {
		updateTasksItemsObjectFromDefinition($id, $definition);
	} else {
		disableTasksItemsOutstanding ($id, $definition);
	}
}

function updateTasksItemsObjectFromDefinition ($id, $definition) {
	if (empty($definition)) {
		$definition_select = usePreparedSelectBlade ("SELECT * FROM TasksDefinition WHERE id = ?", array($id));
		$definition = $definition_select->fetch (PDO::FETCH_ASSOC);
	}

	if ($definition && $definition['enabled'] == 'yes') {
		usePreparedUpdateBlade
		(
			'TasksItem',
			array(
				'object_id' => $definition['object_id'],
				'name' => $definition['name']
			),
			array('definition_id' => $id, 'completed' => 'no')
		);
	}
}
