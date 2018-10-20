<?php

function renderTasksFrequencies ()
{
	echo getTasksFrequencyFormatSuggestionList();

	function printNewItemTR ()
	{
		printOpFormIntro ('add');
		echo '<tr>' .
			'<td>' . getImageHREF ('create', 'add a new frequency', TRUE) . '</td>' .
			'<td><input type=text size=24 name=name></td>' .
			'<td><input type=text size=48 name=format datalist=frequencylist></td>' .
			'<td>&nbsp;</td>' .
			'<td>&nbsp;</td>' .
			'<td>' . getImageHREF ('create', 'add a new frequency', TRUE) . '</td>' .
			'</tr></form>';
	}

	echo '<table cellspacing=0 cellpadding=5 align=center class=widetable>';
	echo '<tr>' .
		'<th>&nbsp;</th>' .
		'<th>name</th>' .
		'<th>format</th>' .
		'<th>item(s)</th>' .
		'<th>next date</th>' .
		'<th>&nbsp;</th>' .
		'</tr>';

	if (getConfigVar ('ADDNEW_AT_TOP') == 'yes')
		printNewItemTR ();

	$date = new DateTime();
	foreach (getTasksFrequencies () as $frequency)
	{
		echo '<tr><td>&nbsp;</td>';
		echo '<td>' . mkA ( stringForLabel ($frequency['name']), 'tasksfrequency', $frequency['id']) . '</td>';
		echo '<td>' . htmlspecialchars ($frequency['format'], ENT_QUOTES, 'UTF-8') . '</td>';
		echo "<td class=tdright>${frequency['num_items']}</td>";
		echo '<td>' . getTasksNextDue($frequency['format'], $date, false) . '</td>';
		echo '<td>&nbsp;</td>';
		echo '</tr></form>';
	}

	if (getConfigVar ('ADDNEW_AT_TOP') != 'yes')
		printNewItemTR ();

	echo '</table>';
}

function renderTasksFrequency ()
{
	if ($freq) {
		echo getTasksFrequencyFormatSuggestionList();
		printOpFormIntro ('upd', array ('id' => $frequency['id']));
		echo '<tr><td>';
		if ($frequency['num_items'])
			printImageHREF ('nodestroy', 'cannot delete, tasks exist');
		else
			echo getOpLink (array ('op' => 'del', 'id' => $frequency['id']), '', 'destroy', 'delete this frequency');
		echo '<td><input type=text size=24 name=name value="' . htmlspecialchars ($frequency['name'], ENT_QUOTES, 'UTF-8') . '"></td>';
		echo '<td><input type=text size=24 name=format list="frequencyList" value="' . htmlspecialchars ($frequency['frequency'], ENT_QUOTES, 'UTF-8') . '"></td>';
		echo '<td>' . getTasksNextDue($freq['format'], $date, false) . '</td>';
		echo '<td>' . getImageHREF ('save', 'update this frequency', TRUE) . '</td>';
		echo '</tr></form>';
		echo '</table>';
	}
}
