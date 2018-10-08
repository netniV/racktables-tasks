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
	$last = new DateTime($definition['processed_time']);
	$time = $last->getTimestamp();
	$freq = $definition['frequency'];
	$next = ($time + $freq);
	$true = ($now >= $next);

	printf("Definition ID: %3d, (%11d + %11d) (%3s) %11d < %s\n", $definition['id'], $time, $freq, $true ? 'Yes':'No', $next, $now);

	if ($true) {
		$dbxlink->beginTransaction();

		$queries = array();
		$queries[] = "
INSERT INTO TasksItem (definition_id, object_id, name, description)
VALUES (
	{$definition['id']},   {$definition['object_id']},
	'{$definition['name']}', '{$definition['description']}'
)
";
		$queries[] = "
UPDATE TasksDefinition SET processed_time = '{$date}' WHERE id = {$definition['id']}
";
		foreach ($queries as $query) {
			try
			{
				$success = $dbxlink->exec ($query) !== FALSE;
			}
			catch (PDOException $e)
			{
				echo (string)$e . PHP_EOL;
				echo PHP_EOL . 'Query: ' . $query .PHP_EOL;
				$success = FALSE;
			}
			if (! $success) {
				$flist[] = $query;
				break;
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
