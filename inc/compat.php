<?php
if (!function_exists('existsConfigVar')) {
	function existsConfigVar ($varname)
	{
		$result = usePreparedSelectBlade
		(
			'SELECT COUNT(*) '.
			'FROM Config C '.
			'WHERE varname = ?',
			array($varname)
		);
		return $result->fetch (PDO::FETCH_NUM);
	}
}
