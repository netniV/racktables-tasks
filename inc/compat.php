<?php
if (!function_exists('existsConfigVar')) {
	function existsConfigVar ($varname)
	{
		global $configCache;
		if (!isset ($configCache))
			throw new RackTablesError ('configuration cache is unavailable', RackTablesError::INTERNAL);

		if (!NULL === $value = array_fetch ($configCache, $varname, NULL))
		{
			$value = 1;
			$result = intval($value);
			$type = "cache";
		}
		else
		{
			$result = usePreparedSelectBlade
			(
				'SELECT COUNT(*) '.
				'FROM Config C '.
				'WHERE varname = ?',
				array($varname)
			);
			$rows = $result->fetch (PDO::FETCH_NUM);
			$value = $rows[0] ?? 'NULL';
			$result = intval($value);
			$type = 'db';
		}

		recordTasksDebug("existsConfigVar ({$varname}) [{$type}]: {$value} ({$result})");

		return $result;
	}
}
