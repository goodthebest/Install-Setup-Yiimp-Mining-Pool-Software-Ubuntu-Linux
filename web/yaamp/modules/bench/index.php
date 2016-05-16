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

<script type="text/javascript">
var algo = '$algo';

$('#algo_select').change(function(event) {
	algo = jQuery('#algo_select').val();
	window.location.href = '/bench?algo='+algo;
});

function page_refresh() {
	bench_refresh();
}

function select_algo(algo) {
	window.location.href = '/bench?algo='+algo;
}

////////////////////////////////////////////////////

function bench_data_ready(data) {
	$('#results').html(data);
}

function bench_refresh() {
	var url = "/bench/results_overall";
	jQuery.get(url, '', bench_data_ready);
}

page_refresh();
jQuery('#algo_select').val(algo);

</script>

<div id="results" style="margin-top: 0;"></div>

end;

