<?php

global $initTasksNavigation;
$initTasksNavigation = array();

foreach(glob(__DIR__ . '/navigation/tasks*.php') as $filename) {
	require_once($filename);
}

function initTasksNavigation() {
	global $initTasksNavigation;

	foreach ($initTasksNavigation as $initNavigation) {
		$initNavigation();
	}
}
