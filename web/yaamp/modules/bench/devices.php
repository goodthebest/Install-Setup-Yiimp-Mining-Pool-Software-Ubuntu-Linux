<?php

include('functions.php');

$this->pageTitle = "Devices";

$devices = array();
$in_db = dbolist("SELECT DISTINCT device, vendorid FROM benchmarks ORDER BY device ASC, vendorid DESC");
foreach ($in_db as $row) {
	$chip = array_pop(explode(' ', $row['device']));
	$devices[$chip] = $chip;
}

$chip = 'all';

$options = '<option value="all">Show all</option>';
foreach($devices as $a => $count) {
	if($a == $chip)
		$options .= '<option value="'.$a.'" selected="selected">'.$a.'</option>';
	else
		$options .= '<option value="'.$a.'">'.$a.'</option>';
}

echo <<<end
<div align="right" style="margin-bottom: 2px; margin-right: 0px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>

<style type="text/css">
tr.ssrow.filtered { display: none; }
td.tick { color: green; font-weight: bolder; }
.page .footer { width: auto; };
</style>

<p style="margin-top: -20px; margin-bottom: 4px; line-height: 22px; font-weight: bolder;">
Devices in database
</p>
end;

$algos_columns = '';
$algos = dbocolumn("SELECT DISTINCT algo FROM benchmarks ORDER BY algo LIMIT 30");
foreach ($algos as $algo) {
	$algos_columns .= '<th>'.$algo.'</th>';
}

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	widgets: ['zebra','filter'],
	textExtraction: {
	//	4: function(node, table, n) { return $(node).attr('data'); }
	},
	widgetOptions: {
		filter_external: '.search',
		filter_columnFilters: false,
		filter_childRows : true,
		filter_ignoreCase: true
	}
}");

echo <<<END
<thead>
<tr>
<th data-sorter="text">Device</th>
<th data-sorter="text">Vendor ID</th>
{$algos_columns}
</tr>
</thead><tbody>
END;

foreach ($in_db as $row) {
	echo '<tr class="ssrow">';

	echo '<td>'.$row['device'].getProductIdSuffix($row).'</td>';
	echo '<td>'.$row['vendorid'].'</td>';

	$records = dbocolumn("SELECT algo FROM benchmarks WHERE vendorid=:vid ", array(':vid'=>$row['vendorid']));
	foreach ($algos as $algo) {
		echo '<td class="tick">'.(in_array($algo, $records) ? '✓' : '&nbsp;').'</td>';
	}

	echo '</tr>';
}

echo '</tbody></table><br/>';
