<?php

include('functions.php');

$algo = user()->getState('bench-algo');
if (empty($algo)) $algo = 'all';

$this->pageTitle = "Benchmarks";

echo '<p style="margin-top: -20px; margin-bottom: 4px; line-height: 25px; font-weight: bolder;">';
if ($algo == 'all') {
	echo "Last 50 results";
} else  {
	echo "Last 150 $algo results";
}
echo '</p>';

if($algo != 'all')
	$db_rows = getdbolist('db_benchmarks', "algo=:algo ORDER BY time DESC LIMIT 50", array(':algo'=>$algo));
else
	$db_rows = getdbolist('db_benchmarks', "1 ORDER BY time DESC LIMIT 150");

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	widgets: ['zebra','filter'],
	textExtraction: {
		1: function(node, table, n) { return $(node).attr('data'); },
		5: function(node, table, n) { return $(node).attr('data'); },
		6: function(node, table, n) { return $(node).attr('data'); }
	},
	widgetOptions: {
		filter_external: '.search',
		filter_columnFilters: false,
		filter_childRows : true,
		filter_ignoreCase: true
	}
}");

$actions = '';
if ($this->admin) {
	$actions = '<th width="30" data-sorter="">Admin</th>';
}

echo <<<END
<thead>
<tr>
<th class="algo" data-sorter="text">Algo</th>
<th data-sorter="text">Time</th>
<th data-sorter="text">Device</th>
<th data-sorter="text">Arch</th>
<th data-sorter="text">Vendor ID</th>
<th data-sorter="numeric">Hashrate</th>
<th data-sorter="numeric">Int</th>
<th data-sorter="numeric">Freq</th>
<th data-sorter="numeric">Watts</th>
<th data-sorter="text">Client</th>
<th data-sorter="text">OS</th>
<th data-sorter="text">Driver</th>
{$actions}
</tr>
</thead><tbody>
END;

foreach ($db_rows as $row) {
	echo '<tr class="ssrow">';

	$hashrate = Itoa2(1000*round($row['khps'],3),3).'H';
	$age = datetoa2($row['time']);

	echo '<td class="algo">'.$row['algo'].'</td>';
	echo '<td data="'.$row['time'].'">'.$age.'</td>';
	echo '<td>'.$row['device'].getProductIdSuffix($row).'</td>';
	echo '<td>'.formatCudaArch($row['arch']).'</td>';
	echo '<td>'.$row['vendorid'].'</td>';
	echo '<td data="'.$row['khps'].'">'.$hashrate.'</td>';
	echo '<td data="'.$row['throughput'].'" title="'.$row['throughput'].' threads">'.$row['intensity'].'</td>';
	echo '<td>'.$row['freq'].'</td>';
	echo '<td>'.(empty($row['power']) ? '-' : $row['power']).'</td>';
	echo '<td>'.$row['client'].'</td>';
	echo '<td>'.$row['os'].'</td>';
	echo '<td>'.$row['driver'].'</td>';

	if ($this->admin) {
		$props = array('style'=>'color: darkred;');
		echo '<td>'.CHtml::link("delete", '/bench/del?id='.$row['id'], $props).'</td>';
	}

	echo '</tr>';
}

echo '</tbody></table><br/>';
