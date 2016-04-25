<?php

$coin = getdbo('db_coins', getiparam('id'));
if (!$coin) $this->goback();

$PoS = ($coin->algo == 'PoS'); // or if 'stake' key is present in 'getinfo' method
$DCR = ($coin->rpcencoding == 'DCR');

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

$reserved1 = dboscalar("SELECT SUM(balance) FROM accounts WHERE coinid={$coin->id}");
$reserved1 = altcoinvaluetoa($reserved1);
$balance = altcoinvaluetoa($coin->balance);

$owed = dboscalar("SELECT SUM(E.amount) AS owed FROM earnings E ".
	"LEFT JOIN blocks B ON E.blockid = B.id ".
	"WHERE E.status!=2 AND E.coinid={$coin->id} "//."AND B.category NOT IN ('stake','generated')"
);
$owed_btc = bitcoinvaluetoa($owed*$coin->price);
$owed = altcoinvaluetoa($owed);

$symbol = $coin->symbol;
if (!empty($coin->symbol2)) $symbol = $coin->symbol2;

echo "<br/>";
if (YAAMP_ALLOW_EXCHANGE) {
	$reserved2 = bitcoinvaluetoa(dboscalar("SELECT SUM(amount*price) FROM earnings
		WHERE status!=2 AND userid IN (SELECT id FROM accounts WHERE coinid={$coin->id})"));
	echo "Earnings $reserved2 BTC, ";
}
echo "Balance (db) $balance $symbol";
echo ", Owed ".CHtml::link($owed, "/site/earning?id=".$coin->id)." $symbol ($owed_btc BTC)";
echo ", ".CHtml::link($reserved1, "/site/payments?id=".$coin->id)." $symbol cleared<br/><br/>";

//////////////////////////////////////////////////////////////////////////////////////

echo <<<end
<style type="text/css">
tr.ssrow.bestmarket { background-color: #dfd; }
tr.ssrow.disabled { background-color: #fdd; color: darkred; }
tr.ssrow.orphan { color: darkred; }
</style>

<table class="dataGrid">
<thead><tr>
<th width="100">Name</th>
<th width="100">Price</th>
<th width="100">Price2</th>
<th width="500">Deposit</th>
<th width="100">Balance</th>
<th width="100">Locked</th>
<th width="100">Sent</th>
<th width="100">Traded</th>
<th width="40">Late</th>
<th align="center" width="500">Message</th>
<th align="right" width="100">Actions</th>
</tr></thead><tbody>
end;

$list = getdbolist('db_markets', "coinid={$coin->id} ORDER BY price DESC");

$bestmarket = getBestMarket($coin);
foreach($list as $market)
{
	$marketurl = '#';
	$price = bitcoinvaluetoa($market->price);
	$price2 = bitcoinvaluetoa($market->price2);

	$marketurl = getMarketUrl($coin, $market->name);

	$rowclass = 'ssrow';
	if($bestmarket && $market->id == $bestmarket->id) $rowclass .= ' bestmarket';
	if($market->disabled) $rowclass .= ' disabled';

	echo '<tr class="'.$rowclass.'">';

	echo '<td><b><a href="'.$marketurl.'" target=_blank>';
	echo $market->name;
	echo '</a></b></td>';

	$updated = "last updated: ".strip_tags(datetoa2($market->pricetime));
	echo '<td title="'.$updated.'">'.$price.'</td>';
	echo '<td title="'.$updated.'">'.$price2.'</td>';

	echo '<td>';
	if (!empty($market->deposit_address)) {
		$name = CJavaScript::encode($market->name);
		$addr = CJavaScript::encode($market->deposit_address);
		echo CHtml::link(
			YAAMP_ALLOW_EXCHANGE ? "sell" : "send",
			"javascript:;", array(
				'onclick'=>"return showSellAmountDialog({$market->id}, $name, $addr);"
			)
		);
		echo ' '.$market->deposit_address;
	}
	echo ' <a href="/market/update?id='.$market->id.'">edit</a>';
	echo '</td>';

	$updated = "last updated: ".strip_tags(datetoa2($market->balancetime));
	$balance = $market->balance > 0 ? bitcoinvaluetoa($market->balance) : '';
	echo '<td title="'.$updated.'">'.$balance.'</td>';

	$ontrade = $market->ontrade > 0 ? bitcoinvaluetoa($market->ontrade) : '';
	echo '<td title="'.$updated.'">'.$ontrade.'</td>';

	$sent = datetoa2($market->lastsent);
	$traded = datetoa2($market->lasttraded);
	$late = $market->lastsent > $market->lasttraded ? 'late': '';

	echo '<td>'.(empty($sent)   ? "" : "$sent ago").'</td>';
	echo '<td>'.(empty($traded) ? "" : "$traded ago").'</td>';
	echo '<td>'.$late.'</td>';

	echo '<td align="center">'.$market->message.'</td>';

	echo '<td align="right">';
	if ($market->disabled)
		echo '<a title="Enable this market" href="/market/enable?id='.$market->id.'&en=1">enable</a>';
	else
		echo '<a title="Disable this market" href="/market/enable?id='.$market->id.'&en=0">disable</a>';
	echo '&nbsp;<a style="color:darkred;" title="Remove this market" href="/market/delete?id='.$market->id.'">delete</a>';
	echo '</td>';

	echo "</tr>";
}

echo "</tbody></table><br>";

//////////////////////////////////////////////////////////////////////////////////////

$info = $remote->getinfo();
if (!empty($info)) {
	$stake = isset($info['stake'])? $info['stake']: '';
	if ($stake !== '') $PoS = true;
}

if ($DCR) {
	// Decred Tickets
	$stake = $remote->getbalance('*',0,'locked');
	$stakeinfo = $remote->getstakeinfo();
	$ticketprice = arraySafeVal($stakeinfo,'difficulty');
	$tickets  = arraySafeVal($stakeinfo, 'live', 0);
	$tickets += arraySafeVal($stakeinfo, 'immature', 0);
}

echo '<table class="dataGrid">';
echo '<thead><tr>';
echo '<th width="30"></th>';
echo '<th width="30"></th>';
echo "<th>Name</th>";
echo "<th>Symbol</th>";
echo "<th>Algo</th>";
echo "<th>Difficulty</th>";
echo "<th>Blocks</th>";
echo "<th>Balance</th>";
echo "<th>BTC</th>";
if ($PoS || $DCR) echo "<th>Stake</th>";
if ($DCR) echo "<th>Ticket price</th>";
echo "<th>Conns</th>";

echo "<th>Price</th>";
echo "<th>Reward</th>";
echo "<th>Index *</th>";

echo "</tr>";
echo "</thead><tbody>";

echo "<tr class='ssrow'>";
echo "<td><img src='$coin->image' width=24></td>";

if($coin->enable)
	echo "<td>[ + ]</td>";
else
	echo "<td>[&nbsp;&nbsp;&nbsp;&nbsp;]</td>";

echo '<td><b><a href="/site/block?id='.$coin->id.'">'.$coin->name.'</a></b></td>';
echo '<td width="60"><b>'.$coin->symbol.'</b></td>';
echo '<td>'.$coin->algo.'</td>';

if(!$info)
{
	echo "<td colspan=8>ERROR $remote->error</td>";
	echo "</tr></tbody></table>";
	return;
}

$errors = isset($info['errors'])? $info['errors']: '';
$balance = isset($info['balance'])? $info['balance']: '';
$txfee = isset($info['paytxfee'])? $info['paytxfee']: '';
$connections = isset($info['connections'])? CHtml::link($info['connections'],'/site/peers?id='.$coin->id): '';
$blocks = isset($info['blocks'])? $info['blocks']: '';

echo '<td>'.round_difficulty($coin->difficulty).'</td>';
if(!empty($errors))
	echo "<td style='color: red;' title='$errors'>$blocks</td>";
else
	echo "<td>$blocks</td>";

echo '<td>'.altcoinvaluetoa($balance).'</td>';

$btc = bitcoinvaluetoa($balance*$coin->price);
echo "<td>$btc</td>";
if ($PoS) echo '<td>'.$stake.'</td>';
if ($DCR) echo '<td>'.CHtml::link("$stake ($tickets)", '/site/tickets?id='.$coin->id).'</td>';
if ($DCR) echo '<td>'.$ticketprice.'</td>';
echo "<td>$connections</td>";

echo '<td>'.bitcoinvaluetoa($coin->price).'</td>';
echo '<td>'.$coin->reward.'</td>';

$index = '';
if($coin->difficulty)
	$index = round($coin->reward * $coin->price / $coin->difficulty * 10000, 3);
echo '<td>'.$index.'</td>';

echo '</tr>';
echo '</tbody></table>';

echo '<br>';

//////////////////////////////////////////////////////////////////////////////////////

// last week
$list_since = arraySafeVal($_GET,'since',time()-(7*24*3600));

$maxrows = arraySafeVal($_GET,'rows', 15);
$maxrows = min($maxrows, 2500);

echo <<<end
<table class="dataGrid">
<thead class="">
<tr>
<th>Time</th>
<th>Category</th>
<th>Amount</th>
<th>Height</th>
<th>Difficulty</th>
<th>Confirm</th>
<th>Address</th>
<th>Tx</th>
</tr>
</thead><tbody>
end;

$account = '';
if ($DCR) $account = '*';

$txs = $remote->listtransactions($account, 2500);

if (empty($txs)) {
	if (!empty($remote->error)) {
		echo "<b>RPC Error: {$remote->error}</b><p/>";
	}
	// retry...
	$txs = $remote->listtransactions($account, 200);
}

$txs_array = array(); $lastday = '';

if (!empty($txs)) {
	// to hide truncated days sums
	$tx = reset($txs);
	if (count($txs) == 2500)
		$lastday = strftime('%F', $tx['time']);

	if (!empty($txs)) foreach($txs as $tx)
	{
		if (intval($tx['time']) > $list_since)
			$txs_array[] = $tx;
	}

	krsort($txs_array);
}

// filter useless decred spent transactions
if ($DCR) {

	$prev_tx = array(); $lastday = '';
	foreach($txs_array as $key => $tx)
	{
		$prev_txid = arraySafeVal($prev_tx,"txid");
		$category = $tx['category'];
		if ($category == 'send' && arraySafeVal($tx,'generated')) {
			$txs_array[$key]['category'] = 'spent';
		}
		else if ($category == 'send' && $prev_txid === arraySafeVal($tx,"txid")) {
			// if txid is the same as the last income... it's not a real "send"
			if ($prev_tx['amount'] == 0 - $tx['amount'])
				$txs_array[$key]['category'] = 'spent';
		}
		else if ($category == 'send' && $tx['amount'] == -0) {
			// vote accepted (listed twice ? in listtransactions)
			if ($tx['vout'] > 0)
				$category = 'spent';
			else if (arraySafeVal($tx,"confirmations") >= 256)
				$category = 'receive';
			else
				$category = 'stake';

			$txs_array[$key]['category'] = $category;

			if ($tx['vout'] == 0) {
				// won ticket value
				$t = $remote->getrawtransaction($tx['txid'], 1);
				if ($t && isset($t['vin'][0])) {
					$txs_array[$key]['amount'] = $t['vin'][0]['amountin'] * 0.00000001;
				}
			}
		}
		else if ($category == 'receive') {
			$prev_tx = $tx;
		}
		// for truncated day sums
		if ($lastday == '' && count($txs) == 2500)
			$lastday = strftime('%F', $tx['time']);
	}
	ksort($txs_array); // reversed order
}

$rows = 0;
foreach($txs_array as $tx)
{
	$category = $tx['category'];
	if ($category == 'spent') continue;

	$block = null;
	if(isset($tx['blockhash']))
		$block = $remote->getblock($tx['blockhash']);

	echo '<tr class="ssrow '.$category.'">';

	$d = datetoa2($tx['time']);
	echo '<td><b>'.$d.'</b></td>';

	echo '<td>'.$category.'</td>';
	echo '<td>'.$tx['amount'].'</td>';

	if($block) {
		echo '<td>'.$block['height'].'</td><td>'.round_difficulty($block['difficulty']).'</td>';
	} else
		echo '<td></td><td></td>';

	if(isset($tx['confirmations']))
		echo '<td>'.$tx['confirmations'].'</td>';
	else
		echo '<td></td>';

	echo '<td width="280">';
	if(isset($tx['address']))
	{
		$address = $tx['address'];
		$exists = dboscalar("SELECT count(*) AS nb FROM accounts WHERE username=:address", array(':address'=>$address));
		if ($exists)
			echo CHtml::link($address, '/?address='.$address);
		else
			echo $address.'<br>';
	}
	echo '</td>';

	echo '<td>';
	if(!empty($block)) {

		$txid = arraySafeVal($tx, 'txid');
		$label = substr($txid, 0, 7);
		echo CHtml::link($label, '/explorer?id='.$coin->id.'&txid='.$txid, array('target'=>'_blank'));
	}
	echo '</td>';

	echo '</tr>';

	$rows++;
	if ($rows >= $maxrows) break;
}

echo '</tbody></table>';

//////////////////////////////////////////////////////////////////////////////////////

echo <<<end
<div id="sums" style="width: 400px; min-height: 250px; float: left; margin-top: 24px; margin-bottom: 8px; margin-right: 16px;">
<table class="dataGrid">
<thead class="">
<tr>
<th>Day</th>
<th>Category</th>
<th>Sum</th>
<th>BTC</th>
</tr>
</thead><tbody>
end;

$sums = array();
foreach($txs_array as $tx)
{
	$day = strftime('%F', $tx['time']); // YYYY-MM-DD
	if ($day == $lastday) break; // do not show truncated days

	$category = $tx['category'];
	if ($category == 'spent') continue;

	$key = $day.' '.$category;
	$sums[$key] = arraySafeVal($sums, $key) + $tx['amount'];
}

foreach($sums as $key => $amount)
{
	$keys = explode(' ', $key);
	$day = substr($keys[0], 5); // remove year
	$category = $keys[1];
	echo '<tr class="ssrow '.$category.'">';
	echo '<td><b>'.$day.'</b></td>';

	echo '<td>'.$category.'</td>';
	echo '<td>'.$amount.'</td>';
	echo '<td>'.bitcoinvaluetoa($coin->price * $amount).'</td>';

	echo '</tr>';
}

if (empty($sums)) {
	echo '<tr class="ssrow">';
	echo '<td colspan="4"><i>No activity during the last 7 days</i></td>';
	echo '</tr>';
}

echo '</tbody></table></div>';

//////////////////////////////////////////////////////////////////////////////////////

if (strpos(YIIMP_WATCH_CURRENCIES, $coin->symbol) === false) return;

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.enhancedLegendRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");

echo <<<end

<style type="text/css">
#graph_history_price, #graph_history_balance {
	width: 75%; height: 300px; float: right;
	margin-top: 16px;
}
.jqplot-title {
	margin-bottom: 3px;
}
.jqplot-cursor-tooltip,
.jqplot-highlighter-tooltip {
	background: rgba(220,220,220, .5) !important;
	border: 1px solid gray;
	padding: 2px 4px;
}
.jqplot-xaxis-tick {
	margin-top: 4px;
}
.jqplot-y2axis-tick {
	font-size: 7pt;
	margin-top: -4px;
	margin-left: 8px;
	width: 36px;
}
.jqplot-table-legend-swatch {
	height: 8px;
	width: 8px;
	margin-top: 2px;
	margin-left: 16px;
}
</style>

<div class="graph" id="graph_history_price"></div>
<div class="graph" id="graph_history_balance"></div>

<script type="text/javascript">

var last_graph_update = 0;

function graph_refresh()
{
	var now = Date.now()/1000;

	if (now < last_graph_update + 1) return;
	last_graph_update = now;

	var w = 0 + $('div#graph_history_price').parent().width();
	w = w - $('div#sums').width() - 32;
	$('.graph').width(w);

	var url = "/site/graphMarketBalance?id={$coin->id}";
	$.get(url, '', graph_balance_data);

	var url = "/site/graphMarketPrices?id={$coin->id}";
	$.get(url, '', graph_price_data);
}

function graph_price_data(data)
{
	var t = $.parseJSON(data);
	var graph = $.jqplot('graph_history_price', t.data,
	{
		title: '<b>Market History</b>',
		animate: false, animateReplot: false,
		axes: {
			xaxis: {
				show: true,
				tickInterval: 600,
				tickOptions: { fontSize: '7pt', escapeHTML: false, formatString:'%#d %b</br>%H:00' },
				renderer: $.jqplot.DateAxisRenderer
			},
			x2axis: {
				// hidden (top) axis with higher granularity
				syncTicks: 1,
				tickInterval: 600,
				tickOptions: { show: false },
				renderer: $.jqplot.DateAxisRenderer
			},
			y2axis: {
				min: t.rangeMin, max: t.rangeMax
			}
		},

		seriesDefaults: {
			xaxis: 'x2axis',
			yaxis: 'y2axis',
			showLabel: true,
			markerOptions: { style: 'circle', size: 2 }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#f0f0f0'
		},

		legend: {
			labels: t.labels,
			renderer: jQuery.jqplot.EnhancedLegendRenderer,
			rendererOptions: { numberRows: 1 },
			location: 'n',
			show: true
		},

		highlighter: {
			useAxesFormatters: false,
			tooltipContentEditor: function(str, seriesIndex, pointIndex, jqPlot) {
				var pt = jqPlot.series[seriesIndex].data[pointIndex];
				var dt = new Date(0+pt[0]);
				var date = $.datepicker.formatDate('dd M yy', dt);
				var time = dt.getHours().toString()+'h'+dt.getMinutes();
				return date+' '+time+' ' + pt[1]+' BTC';
			},
			show: true
		}
	});
	var x2 = graph.axes.x2axis;
	for (var i=0; i < x2._ticks.length; i++) {
		// put in visible axis, only one tick per hour...
		if (i % 12 == 0) {
			graph.axes.xaxis.ticks.push(x2._ticks[i].value);
		}
	}
	graph.replot(false);
}

function graph_balance_data(data)
{
	var t = $.parseJSON(data);
	var graph = $.jqplot('graph_history_balance', t.data,
	{
		title: '<b>Market Balances</b>',
		animate: false, animateReplot: false,
		stackSeries: true,
		axes: {
			xaxis: {
				show: true,
				tickInterval: 600,
				tickOptions: { fontSize: '7pt', escapeHTML: false, formatString:'%#d %b</br>%#Hh' },
				showMinorTicks: false,
				renderer: $.jqplot.DateAxisRenderer
			},
			x2axis: {
				// hidden (top) axis with higher granularity
				syncTicks: 1,
				tickInterval: 600,
				tickOptions: { show: false },
				renderer: $.jqplot.DateAxisRenderer
			},
			y2axis: {
				min: t.rangeMin, max: t.rangeMax
			}
		},

		seriesDefaults: {
			xaxis: 'x2axis',
			yaxis: 'y2axis',
			fill: true,
			showLabel: true,
			markerOptions: { style: 'circle', size: 2 }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#f0f0f0'
		},

		legend: {
			labels: t.labels,
			renderer: jQuery.jqplot.EnhancedLegendRenderer,
			rendererOptions: { numberRows: 1 },
			location: 'n',
			show: true
		},

		highlighter: {
			useAxesFormatters: false,
			tooltipContentEditor: function(str, seriesIndex, pointIndex, jqPlot) {
				var pt = jqPlot.series[seriesIndex].data[pointIndex];
				var dt = new Date(0+pt[0]);
				var date = $.datepicker.formatDate('dd M yy', dt);
				var time = dt.getHours().toString()+'h';
				return date+' '+time+' ' + pt[1]+' {$coin->symbol}';
			},
			show: true
		}
	});
	var x2 = graph.axes.x2axis;
	for (var i=0; i < x2._ticks.length; i++) {
		// put in visible axis, only one tick per hour...
		if (i % 12 == 0) {
			graph.axes.xaxis.ticks.push(x2._ticks[i].value);
		}
	}
	graph.replot(false);
}
</script>
end;

JavascriptReady("graph_refresh(); $(window).resize(graph_refresh);");