#!/usr/bin/env php
<?php

$script_mode = TRUE;
$remote_user = 'admin';

require_once __DIR__ . '/../../wwwroot/inc/init.php';
require_once __DIR__ . '/plugin.php';

error_reporting(E_ALL);
echo "Searching ... " . PHP_EOL;
$modes = getTasksModes();
$now = new DateTime();
foreach ($argv as $arg) {
	$color = 'transparent';
	$incomplete = '';

	$task_item_id = intval($arg);
	if ($task_item_id > 0) {
		$tasks	= getTasksItems (0, NULL, $task_item_id);

		foreach ($tasks as $task) {
			$mode = $modes[$task['mode']];
			$due	= $now;
			$created = new DateTime($task['created_time']);
			$diff  = $now->diff($created);

			echo "[=== Task #{$task['id']} - Mode: {$mode} ===]" . PHP_EOL;
			echo " {$task['name']} ({$task['description']})" . PHP_EOL . PHP_EOL;
			echo " - Created..: " . $created->format('Y-m-d H:i:s') . PHP_EOL;
			if ($task['completed'] == 'yes') {
				$completed = new DateTime($task['completed_time']);
				$due = $completed;
				echo " - Completed: ";
			} else {
				echo " - Pending..: ";
			}

			echo $due->format('Y-m-d H:i:s');
			if (!empty($task['completed_by'])) {
				echo " (by {$task['completed_by']})";
			}
			echo PHP_EOL;

			if ($created <= $due) {
				echo " - Frequency: {$task['frequency_format']}" . PHP_EOL;

				$freq  = $task['frequency_format'];
				$next  = clone $created;
				$count = 0;

				while ($count < 2 && $next < $due) {
					echo "   - Next  {$count}: " . $next->format('Y-m-d H:i:s') . PHP_EOL;
					$count++;
					$next = getTasksNextDue($freq, $next);
				}
				echo "   - Final {$count}: " . $next->format('Y-m-d H:i:s') . PHP_EOL;

				if ($count > 2) {
					$color = 'late';
				} else if ($count > 1) {
					$color = 'overdue';
				} else {
					$color = 'pastdue';
				}
			}
			echo " - Color....: {$color}" . PHP_EOL;
			echo PHP_EOL;
		}
	}
}
