<?php

$algo = user()->getState('bench-algo');
if (empty($algo)) $algo = 'all';

$this->pageTitle = "Benchmarks";
if($algo != 'all')
	$db_rows = getdbolist('db_benchmarks', "algo=:algo ORDER BY time DESC LIMIT 50", array(':algo'=>$algo));
else
	$db_rows = getdbolist('db_benchmarks', "1 ORDER BY time DESC LIMIT 150");

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	widgets: ['zebra','filter'],
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
<th class="algo" data-sorter="text">Algo</th>
<th data-sorter="text">Type</th>
<th data-sorter="text">Device</th>
<th data-sorter="numeric">Hashrate</th>
<th data-sorter="numeric">Int</th>
<th data-sorter="numeric">Freq</th>
<th data-sorter="numeric">Power</th>
<th data-sorter="text">Client</th>
<th data-sorter="text">OS</th>
<th data-sorter="text">Driver</th>
</tr>
</thead><tbody>
END;

foreach ($db_rows as $row) {
	echo '<tr class="ssrow">';

	echo '<td class="algo">'.$row['algo'].'</td>';
	echo '<td>'.$row['type'].'</td>';
	echo '<td title="'.$row['vendorid'].' '.$row['arch'].'">'.$row['device'].'</td>';
	echo '<td>'.round($row['khps'],3).'</td>';
	echo '<td title="'.$row['throughput'].' threads">'.$row['intensity'].'</td>';
	echo '<td>'.$row['freq'].'</td>';
	echo '<td>'.(empty($row['power']) ? '-' : $row['power']).'</td>';
	echo '<td>'.$row['client'].'</td>';
	echo '<td>'.$row['os'].'</td>';
	echo '<td>'.$row['driver'].'</td>';

	echo '</tr>';
}

echo '</tbody></table><br/>';
