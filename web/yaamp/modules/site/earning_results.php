<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

echo <<<end
<div align="right" style="margin-top: -14px; margin-bottom: 6px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>
<style type="text/css">
tr.ssrow.filtered { display: none; }
</style>
end;

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	headers: {
		0:{sorter:false},
		1:{sorter:'text'},
		2:{sorter:'text'},
		3:{sorter:'currency'},
		4:{sorter:'numeric'},
		5:{sorter:'metadata'},
		6:{sorter:'metadata'},
		7:{sorter:false}
	},
	widgets: ['zebra','filter','Storage','saveSort'],
	widgetOptions: {
		saveSort: true,
		filter_saveFilters: true,
		filter_external: '.search',
		filter_columnFilters: false,
		filter_childRows : true,
		filter_ignoreCase: true
	}
}");

echo "<thead>";
echo "<tr>";
echo "<th width=20></th>";
echo "<th>Coin</th>";
echo "<th>Wallet</th>";
//echo "<th>Status</th>";
//echo "<th>Amount</th>";
echo "<th>Quantity</th>";
echo "<th>Block</th>";
echo "<th>Status</th>";
echo "<th>Sent</th>";
echo "<th></th>";
echo "</tr>";
echo "</thead><tbody>";

$earnings = getdbolist('db_earnings', "status!=2 ORDER BY create_time DESC LIMIT 500");

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
}

echo "</tbody></table>";


