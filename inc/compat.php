<?php
if (!function_exists('existsConfigVar')) {
	function existsConfigVar ($varname)
	{
		global $configCache;
		if (!isset ($configCache))
			throw new RackTablesError ('configuration cache is unavailable', RackTablesError::INTERNAL);

		if (!NULL === $value = array_fetch ($configCache, $varname, NULL))
		{
			recordTasksDebug("existsConfigVar ($varname) [cache]: 1");
			$value = 1;
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
			$value = count ($rows) == 1 ? $rows[0][0] : 'NULL';
			recordTasksDebug("existsConfigVar ($varname) [db]: $value");
		}

		return intval($value);
	}
}
