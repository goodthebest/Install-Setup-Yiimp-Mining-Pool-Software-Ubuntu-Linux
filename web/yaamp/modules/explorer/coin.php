<?php

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");

$this->pageTitle = $coin->name." bloc explorer";

if ($coin) echo <<<ENDJS
	<script>
	$(function() {
		$('#favicon').remove();
		$('head').append('<link href="{$coin->image}" id="favicon" rel="shortcut icon">');
	});
	</script>
ENDJS;

echo "<br>";
echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>$coin->name Explorer</div>";
echo "<div class='main-left-inner'>";

echo "<table  class='dataGrid2'>";

echo "<thead>";
echo "<tr>";
echo "<th>Time</th>";
echo "<th>Height</th>";
echo "<th>Diff</th>";
echo "<th>Transactions</th>";
echo "<th>Confirmations</th>";
echo "<th>Blockhash</th>";
echo "</tr>";
echo "</thead>";

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
for($i = $coin->block_height; $i > $coin->block_height-25; $i--)
{
	$hash = $remote->getblockhash($i);
	if(!$hash) continue;

	$block = $remote->getblock($hash);
	if(!$block) continue;

	$d = datetoa2($block['time']);
	$confirms = isset($block['confirmations'])? $block['confirmations']: '';
	$tx = count($block['tx']);
	$diff = $block['difficulty'];

//	debuglog($block);
	echo "<tr class='ssrow'>";
	echo "<td>$d</td>";
	echo "<td><a href='/explorer?id=$coin->id&height=$i'>$i</a></td>";
	echo "<td>$diff</td>";
	echo "<td>$tx</td>";
	echo "<td>$confirms</td>";
	echo "<td><span style='font-family: monospace;'><a href='/explorer?id=$coin->id&hash=$hash'>$hash</a></span></td>";

	echo "</tr>";
}

echo "</table>";

echo <<<end
<form action="/explorer" method="get" style="padding: 10px; width: 250px;">
<input type="hidden" name="id" value="{$coin->id}">
<input type="text" name="height" class="main-text-input" placeholder="block height" style="max-width: 100px;">
<input type="submit" value="Submit" class="main-submit-button" >
</form>

<div id="diff_graph" style="margin-right: 8px;">
<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
</div>

<script type="text/javascript" event="">

var last_graph_update = 0;

function graph_refresh()
{
	var now = Date.now()/1000;

	if (now < last_graph_update + 900) return;
	last_graph_update = now;

	var url = "/explorer/graph?id={$coin->id}";
	$.get(url, '', diff_graph_data);
}

function diff_graph_data_trace(data)
{
	 $('#diff_graph').html(data);
}

function diff_graph_data(data)
{
	var t = $.parseJSON(data);
	var plot1 = $.jqplot('diff_graph', t,
	{
		title: '<b>Difficulty</b>',
		axes: {
			xaxis: {
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size="1">%H:%M</font>'}
			},
			yaxis: {
				min: 0,
				tickOptions: {formatString: '<font size="1">%#.3f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			markerOptions: { style: 'none' }
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#ffffff'
		},

		highlighter:
		{
			show: true
		},

	});
}
</script>
end;

app()->clientScript->registerScript('graph',"
	graph_refresh();
", CClientScript::POS_READY);