<?php

if (empty($algo)) $algo = 'all';

$algos = array();
$in_db = dbolist("SELECT algo, count(id) as count FROM benchmarks GROUP BY algo ORDER BY algo ASC, count DESC");
foreach ($in_db as $row) {
	$algos[$row['algo']] = $row['count'];
}

$options = '<option value="all">Show all</option>';
foreach($algos as $a => $count) {
	if($a == $algo)
		$options .= '<option value="'.$a.'" selected="selected">'.$a.'</option>';
	else
		$options .= '<option value="'.$a.'">'.$a.'</option>';
}

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");


include('functions.php');

$algo = user()->getState('bench-algo');
if (empty($algo)) $algo = 'all';
if (empty($vid)) $vid = NULL;

$this->pageTitle = "Benchmarks";

$bench = new db_benchmarks;
if($algo != 'all') $bench->algo = $algo;
$bench->vendorid = $vid;
$dp = $bench->search();
$db_rows = $dp->getData();

echo <<<end

<div align="right" style="margin-bottom: 2px; margin-right: 0px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>

<style type="text/css">
tr.ssrow.filtered { display: none; }
.page .footer { width: auto; };
</style>

<div align="right" style="margin-top: -22px; margin-right: 140px;">
Select Algo: <select id="algo_select">{$options}</select>&nbsp;
</div>

end;

echo '<p style="margin-top: -20px; margin-bottom: 4px; line-height: 22px; font-weight: bolder;">';
if ($algo == 'all') {
	echo "Last 50 results";
} else  {
	echo "Last 50 $algo results";
}
echo '</p>';

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
<th data-sorter="numeric" title="Intensity (-i) for GPU or Threads (-t) for CPU miners">Int.</th>
<th data-sorter="numeric" title="MHz">Freq</th>
<th data-sorter="numeric" title="Watts if available">W</th>
<th data-sorter="text">Client</th>
<th data-sorter="text">OS</th>
<th data-sorter="text">Driver / Compiler</th>
{$actions}
</tr>
</thead><tbody>
END;

foreach ($db_rows as $row) {

	if ($vid && $row['vendorid'] != $vid) continue;

	echo '<tr class="ssrow">';

	$hashrate = Itoa2(1000*round($row['khps'],3),3).'H';
	$age = datetoa2($row['time']);

	echo '<td class="algo">'.CHtml::link($row['algo'],'/bench?algo='.$row['algo']).'</td>';
	echo '<td data="'.$row['time'].'">'.$age.'</td>';
	if ($row['type'] == 'cpu') {
		echo '<td>'.formatCPU($row).'</td>';
		echo '<td>'.$row['arch'].'</td>';
		echo '<td>'.$row['vendorid'].'</td>';
	} else {
		echo '<td>'.$row['device'].getProductIdSuffix($row).'</td>';
		echo '<td>'.formatCudaArch($row['arch']).'</td>';
		echo '<td>'.CHtml::link($row['vendorid'],'/bench?vid='.$row['vendorid']).'</td>';
	}

	echo '<td data="'.$row['khps'].'">'.$hashrate.'</td>';

	if ($row['type'] == 'cpu') // threads
		echo '<td data="'.$row['throughput'].'">'.$row['throughput'].'</td>';
	else if ($algo == 'neoscrypt')
		echo '<td data="'.$row['throughput'].'" title="neoscrypt intensity is different">'.$row['throughput'].'*</td>';
	else
		echo '<td data="'.$row['throughput'].'" title="'.$row['throughput'].' threads">'.$row['intensity'].'</td>';

	echo '<td>'.($row['freq'] ? $row['freq'] : '-').'</td>';
	echo '<td>'.(empty($row['power']) ? '-' : $row['power']).'</td>';
	echo '<td>'.formatClientName($row['client']).'</td>';
	echo '<td>'.$row['os'].'</td>';
	echo '<td>'.$row['driver'].'</td>';

	if ($this->admin) {
		$props = array('style'=>'color: darkred;');
		echo '<td>'.CHtml::link("delete", '/bench/del?id='.$row['id'], $props).'</td>';
	}

	echo '</tr>';
}

echo '</tbody></table><br/>';

echo <<<end

<p style="margin: 0; padding: 0 4px;">
<a href="/bench/devices">Show current state of the database (devices/algos)</a><br/>
<br/>
</p>

<script type="text/javascript">

$('#algo_select').change(function(event) {
	algo = jQuery('#algo_select').val();
	window.location.href = '/bench?algo='+algo;
});

</script>

end;
