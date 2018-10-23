<?php

function assertTasksParam ($argname, $argtype) {
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
		case 'date0':
			if (!empty($sic[$argname])) {
				try {
					$d = new DateTime($sic[$argname]);
				} catch (Exception $e) {
					throw new InvalidRequestArgException ($argname, $sic[$argname], 'Invalid DateTime');
				}
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

function getTasksDiffValue($date1, $date2) {
	return $date2->diff($date1)->format('%a');;
}

function getTasksNextDue($freq, $date = null, $throw = true) {
	$date_orig = $date != null ? $date : new DateTime();
	$date_freq = clone($date_orig);

	try {
		$freqs = parseTasksFrequency($freq);
		if (!empty($freqs['data'])) {
			$items = explode(';', $freqs['data']);
			foreach ($items as $item) {
				if ($item[0] == 'P') {
					$interval = new DateInterval($item);
					$date_freq = $date_freq->add($interval);
				} else {
					$date_freq = $date_freq->modify($item);
				}
			}
		}
	} catch (Exception $e) {
		if (!$throw) {
			return $e->getMessage();
		}
	}

	if ($date_orig->getTimestamp() == $date_freq->getTimestamp()) {
		if ($throw) {
			$message = 'unable to get next due date';
			foreach ($lines = debug_backtrace() as $line) {
				$message .= ', ' . basename($line['file']) . '[' . $line['line'] . ']: ' .
					(isset($line['class']) ? ($line['class'] . '::') : '') . $line['function'] .'()';
			}

			throw new RTDatabaseError($message);
		} else {
			return $date_orig->getTimestamp() . ': ' . $freq;
		}
	}
	return $throw ? $date_freq : $date_freq->format('Y-m-d H:i:s');
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

function getTasksFrequencyFormatSuggestionList() {
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

function isTasksDebugUser() {
	global $remote_username;

	return ($remote_username == 'netniv' ||	$remote_username == 'admin');
}

function recordTasksDebug($message) {
	if (isTasksDebugUser()) {
		error_log($message);
	}
}
