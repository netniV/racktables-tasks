<?php

foreach(glob(__DIR__ . '/navigation/tasks*.php') as $filename) {
	require_once($filename);
}

