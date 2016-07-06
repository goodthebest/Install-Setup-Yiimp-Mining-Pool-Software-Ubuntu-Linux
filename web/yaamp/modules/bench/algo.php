<?php

include('functions.php');

if (empty($algo)) $algo = 'all';
$algoFilter = $algo != 'all' ? ' AND B.algo='.sqlQuote($algo) : '';

$this->pageTitle = "Algo benchmarks";

// -------------------------------------------------
$algos = array();
$in_db = dbolist("SELECT algo, count(id) as count FROM benchmarks B GROUP BY algo ORDER BY algo ASC, count DESC");
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
// -------------------------------------------------

$in_db = dbolist("SELECT B.type, B.idchip, C.chip,
	AVG(B.khps) as khps, AVG(B.power) as power, AVG(B.intensity) as intensity, AVG(B.freq) as freq
	FROM benchmarks B
	LEFT JOIN bench_chips C ON C.id = B.idchip
	WHERE B.idchip > 0 $algoFilter
	GROUP BY B.type, B.idchip ORDER BY khps DESC
");

echo <<<end
<div align="right" style="margin-bottom: 2px; margin-right: 0px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>

<style type="text/css">
tr.ssrow.filtered { display: none; }
td.tick { font-weight: bolder; }
span.generic { color: gray; }
.page .footer { width: auto; };
</style>

<div align="right" style="margin-top: -22px; margin-right: 140px;">
Select Algo: <select class="filter" id="algo_select">{$options}</select>&nbsp;
</div>

<p style="margin-top: -20px; margin-bottom: 4px; line-height: 22px; font-weight: bolder;">
Overall $algo performance
</p>
end;

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	widgets: ['zebra','filter'],
	textExtraction: {
		2: function(node, table, n) { return $(node).attr('data'); }
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
<th data-sorter="text" width="50">Type</th>
<th data-sorter="text" width="70">Chip</th>
<th data-sorter="numeric" width="220">Hashrate</th>
<th data-sorter="numeric" width="220">Power</th>
<th data-sorter="numeric" width="220">Int</th>
<th data-sorter="numeric" width="220">Freq</th>
</tr>
</thead><tbody>
END;

foreach ($in_db as $row) {
	echo '<tr class="ssrow">';

	echo '<td>'.strtoupper($row['type']).'</td>';
	$power = dboscalar('SELECT AVG(power) FROM benchmarks B WHERE idchip='.$row['idchip'].$algoFilter.' AND power > 10');

	$chip = CHtml::link($row['chip'], '/bench?chip='.$row['idchip'].'&algo='.$algo);
	echo '<td>'.$chip.'</td>';

	echo '<td data="'.$row['khps'].'">'.Itoa2(1000*round($row['khps'],3),3).'H</td>';
	echo '<td>'.($power>0 ? round($power) : '-').'</td>';
	echo '<td>'.($row['intensity']>0 ? round($row['intensity']) : '-').'</td>';
	echo '<td>'.($row['freq']>0 ? round($row['freq']) : '-').'</td>';

	echo '</tr>';
}

echo '</tbody></table><br/>';

echo '<a href="/bench/devices">Show current state of the database (devices/algos)</a><br/>';
echo '<a href="/site/benchmarks">Learn how to submit your results</a>';
echo '<br/><br/>';

echo <<<end

<script type="text/javascript">

$('select.filter').change(function(event) {
	algo = jQuery('#algo_select').val();
	window.location.href = '/bench/algo?algo='+algo;
});

</script>

end;
