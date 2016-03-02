<?php

// PS: account last_login is not related to user logins... its the last backend payment loop, todo: rename it..

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

echo <<<end
<div align="right" style="margin-top: -14px; margin-bottom: 6px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>
<style type="text/css">
tr.ssrow.filtered { display: none; }
.currency { width: 120px; max-width: 180px; text-align: right; }
.red { color: darkred; }
.actions { width: 120px; text-align: center; }
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
		0:{sorter:'metadata'},
		1:{sorter:'text'},
		2:{sorter:'text'},
		3:{sorter:'text'},
		4:{sorter:'currency'},
		5:{sorter:'currency'},
		6:{sorter:'currency'},
		7:{sorter:'currency'},
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
<th>Last block</th>
<th class="currency">Pool</th>
<th class="currency">Balance</th>
<th class="currency">Immature</th>
<th class="currency">Failed</th>
<th class="actions">Actions</th>
</tr>
</thead><tbody>
end;

$sqlFilter = $coin_id ? "AND coinid={$coin_id}" : "";

$data = dbolist("SELECT coinid, userid, SUM(amount) AS immature FROM earnings WHERE status=0 $sqlFilter GROUP BY coinid, userid");
$immature = array();
if (!empty($data)) foreach ($data as $row) {
	$immkey = $row['coinid']."-".$row['userid'];
	$immature[$immkey] = $row['immature'];
}

$data = dbolist("SELECT account_id, SUM(amount) AS failed FROM payouts WHERE tx IS NULL AND completed=0 GROUP BY account_id");
$failed = array();
if (!empty($data)) foreach ($data as $row) {
	$uid = $row['account_id'];
	$failed[$uid] = $row['failed'];
}

$list = getdbolist('db_accounts', "coinid!=6 $sqlFilter AND (".
	"balance > 0 OR last_login > (UNIX_TIMESTAMP()-60*60) OR id IN (SELECT DISTINCT account_id FROM payouts WHERE tx IS NULL)".
	") ORDER BY last_login DESC limit 50");

$total = 0.; $totalimmat = 0.; $totalfailed = 0.;
foreach($list as $user)
{
	$coin = getdbo('db_coins', $user->coinid);
	$d = datetoa2($user->last_login);

	echo '<tr class="ssrow">';

	if($coin) {
		$coinbalance = $coin->balance ? bitcoinvaluetoa($coin->balance) : '-';
		echo '<td><img width="16" src="'.$coin->image.'"></td>';
		echo '<td><b><a href="/site/coin?id='.$coin->id.'">'.$coin->name.'</a></b>&nbsp;('.$coin->symbol_show.')</td>';
		$immkey = "{$coin->id}-{$user->id}";
	} else {
		$coinbalance = '-';
		echo '<td></td>';
		echo '<td></td>';
		$immkey = "0-{$user->id}";
	}

	echo '<td><a href="/?address='.$user->username.'"><b>'.$user->username.'</b></a></td>';
	echo '<td>'.$d.'</td>';

	echo '<td class="currency">'.$coinbalance.'</td>';

	$balance = $user->balance ? bitcoinvaluetoa($user->balance) : '-';
	$total += (double) $user->balance;
	echo '<td class="currency">'.$balance.'</td>';

	$immbalance = arraySafeVal($immature, $immkey, 0);
	$totalimmat += (double) $immbalance;
	$immbalance = $immbalance ? bitcoinvaluetoa($immbalance) : '-';
	echo '<td class="currency">'.$immbalance.'</td>';

	$failbalance = arraySafeVal($failed, $user->id, 0);
	$totalfailed += (double) $failbalance;
	$failbalance = $failbalance ? bitcoinvaluetoa($failbalance) : '-';
	echo '<td class="currency red">'.$failbalance.'</td>';

	echo '<td class="actions">';
	if ($failbalance != '-')
		echo '<a href="/site/cancelUserPayment?id='.$user->id.'">[add to balance]</a>';
	echo '</td>';

	echo "</tr>";
}

echo "</tbody></table>";

if ($coin_id) {
	$coin = getdbo('db_coins', $coin_id);
	$symbol = $coin->symbol;
	echo '<div class="totals" align="right">';
	echo '<table class="totals">';
	echo '<tr><th>Balances</th><td>'.bitcoinvaluetoa($total)." $symbol</td></tr>";
	echo '<tr><th>Immature</th><td>'.bitcoinvaluetoa($totalimmat)." $symbol</td></tr>";
	if ($totalfailed)
		echo '<tr class="red"><th>Failed</th><td>'.bitcoinvaluetoa($totalfailed)." $symbol</td></tr>";
	echo '</tr></table>';
	echo '</div>';
}