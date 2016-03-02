<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

echo <<<end
<div align="right" style="margin-top: -14px; margin-bottom: 6px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>
<style type="text/css">
tr.ssrow.filtered { display: none; }
table.totals { margin-top: 8px; margin-right: 16px; }
table.totals th { text-align: left; width: 100px; }
table.totals td { text-align: right; }
table.totals tr.red td { color: darkred; }
</style>
end;

$coin_id = getiparam('id');

$saveSort = $coin_id ? 'false' : 'true';

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	headers: {
		0:{sorter:false},
		1:{sorter:'text'},
		2:{sorter:'text'},
		3:{sorter:'currency'},
		4:{sorter:'currency'},
		5:{sorter:'numeric'},
		6:{sorter:'metadata'},
		7:{sorter:'metadata'},
		8:{sorter:false}
	},
	widgets: ['zebra','filter','Storage','saveSort'],
	widgetOptions: {
		saveSort: {$saveSort},
		filter_saveFilters: {$saveSort},
		filter_external: '.search',
		filter_columnFilters: false,
		filter_childRows : true,
		filter_ignoreCase: true
	}
}");

echo <<<end
<thead>
<tr>
<th width="20"></th>
<th>Coin</th>
<th>Address</th>
<th class="currency">Quantity</th>
<th class="currency">BTC</th>
<th>Block</th>
<th>Status</th>
<th>Sent</th>
<th></th>
</tr>
</thead><tbody>
end;

$coin_id = getiparam('id');
$sqlFilter = $coin_id ? "AND coinid={$coin_id}": '';

$earnings = getdbolist('db_earnings', "status!=2 $sqlFilter ORDER BY create_time DESC LIMIT 1000");

$total = 0.; $total_btc = 0.; $totalimmat = 0.; $totalfees = 0.; $totalstake = 0.;

foreach($earnings as $earning)
{
//	if(!$earning) debuglog($earning);
	$coin = getdbo('db_coins', $earning->coinid);
	if(!$coin) continue;

	$user = getdbo('db_accounts', $earning->userid);
	if(!$user) continue;

	$block = getdbo('db_blocks', $earning->blockid);
	if(!$block) continue;

	$t1 = datetoa2($earning->create_time). ' ago';
	$t2 = datetoa2($earning->mature_time);
	if ($t2) $t2 = '+'.$t2;

	$coinimg = CHtml::image($coin->image, $coin->symbol, array('width'=>'16'));
	$coinlink = CHtml::link($coin->name, '/site/coin?id='.$coin->id);

	echo "<tr class='ssrow'>";
	echo "<td>$coinimg</td>";
	echo "<td><b>$coinlink</b>&nbsp;($coin->symbol_show)</td>";
	echo '<td><b><a href="/?address='.$user->username.'">'.$user->username.'</a></b></td>';
	echo '<td>'.bitcoinvaluetoa($earning->amount).'</td>';
	echo '<td>'.bitcoinvaluetoa($earning->amount * $earning->price).'</td>';
	echo "<td>$block->height</td>";
	echo "<td>$block->category ($block->confirmations)</td>";
	echo '<td data="'.$earning->create_time.'">'."$t1 $t2</td>";

	echo "<td>
		<a href='/site/clearearning?id=$earning->id'>[clear]</a>
		<a href='/site/deleteearning?id=$earning->id'>[delete]</a>
		</td>";

//	echo "<td style='font-size: .7em'>$earning->tx</td>";
	echo "</tr>";

// 	if($block->category == 'generate' && $earning->status == 0)
// 	{
// 		$earning->status = 1;
// 		$earning->mature_time = time()-100*60;
// 		$earning->save();
// 	}

	if($block->category == 'immature') {
		$total += (double) $earning->amount;
		$total_btc += (double) $earning->amount * $earning->price;
		$totalimmat += (double) $earning->amount;
	}
	if($block->category == 'generate') {
		$total += (double) $earning->amount; // "Exchange" state
		$total_btc += (double) $earning->amount * $earning->price;
	}
	else if($block->category == 'stake' || $block->category == 'generated') {
		$totalstake += (double) $earning->amount;
	}
}

echo "</tbody></table>";

if ($coin_id) {
	$coin = getdbo('db_coins', $coin_id);
	if (!$coin) exit;
	$symbol = $coin->symbol;
	$feepct = yaamp_fee($coin->algo);
	$totalfees = ($total / ((100 - $feepct) / 100.)) - $total;

	echo '<div class="totals" align="right">';
	echo '<table class="totals">';
	echo '<tr><th>Immature</th><td>'.bitcoinvaluetoa($totalimmat)." $symbol</td></tr>";
	echo '<tr><th>Total</th><td>'.bitcoinvaluetoa($total)." $symbol</td></tr>";
	//echo '<tr><th>Total BTC</th><td>'.bitcoinvaluetoa($total_btc)." BTC</td></tr>";
	echo '<tr><th>Pool Fees '.round($feepct,1).'%</th><td>'.bitcoinvaluetoa($totalfees)." $symbol</td></tr>";
	if ($coin->rpcencoding == 'POS')
		echo '<tr><th>Stake</th><td>'.bitcoinvaluetoa($totalstake)." $symbol</td></tr>";
	echo '<tr><th>Available</th><td>'.bitcoinvaluetoa($coin->balance)." $symbol</td></tr>";
	echo '</tr></table>';
	echo '</div>';
}