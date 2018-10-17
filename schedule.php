#!/usr/bin/env php
<?php

$script_mode = TRUE;
require_once __DIR__ . '/../../wwwroot/inc/init.php';
require_once __DIR__ . '/plugin.php';

// Try one row per query at a time so the user can see which values failed, if any.
$definitions = getTasksDefinitions();

$now = time();
$date = date('Y-m-d H:i:s');
$flist = array();
foreach ($definitions as $definition)
{
	if ($definition['mode'] == 'schedule') {
		$last = new DateTime($definition['processed_time']);
		$freq = $definition['frequency'];
		$next = getTasksNextDue($freq, $last);
		$true = ($last >= $next);

		printf("Definition ID: %3d, (%11d + %11d) (%3s) %11d < %s\n", $definition['id'], $last->getTimestamp(), $next->getTimestamp(), $true ? 'Yes':'No', $next, $now);

		if ($true) {
			$dbxlink->beginTransaction();

			try
			{
				insertTasksItem ($definition['id'], $definition['mode'], $definition['name'], $definition['description'], $definition['object_id'], $next);
				updateTasksDefinitionProcessedTime ($definition['id'], $date);
			}
			catch (PDOException $e)
			{
				$success = FALSE;
				$flist[] = $definition;
			}
		}
		if (!$success)
			$dbxlink->rollBack();
		else
			$dbxlink->commit();
	}
}

if (count ($flist))
{
	exit (1);
}
else
{
	exit (0);
}
