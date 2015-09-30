<?php

/////////////////////////////////////////////////////////////////////////////////////////////////

$symbol = getparam('symbol');
$coin = null;

if($symbol == 'all')
	$users = getdbolist('db_accounts', "balance>.001 order by balance desc");
else
{
	$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
	if(!$coin) return;

	$users = getdbolist('db_accounts', "balance>.001 and coinid=$coin->id order by balance desc");
}

//echo "<br><table class='dataGrid'>";
showTableSorter('maintable', "{
	tableClass: 'dataGrid'
}");

echo "<thead>";
echo "<tr>";
echo "<th>UID</th>";
echo "<th></th>";
echo "<th>Coin</th>";
echo "<th>Address</th>";
echo "<th>Last</th>";
echo "<th align=right>Miners</th>";
echo "<th align=right>Hashrate</th>";
echo "<th align=right>Bad</th>";
echo "<th></th>";
echo "<th align=right>Blocks</th>";
echo "<th align=right>Diff/Paid</th>";
echo "<th align=right>Balance</th>";
echo "<th align=right>Total Paid</th>";
echo "<th></th>";
echo "</tr>";
echo "</thead><tbody>";

$total_balance = 0;
$total_paid = 0;
$total_unsold = 0;

foreach($users as $user)
{
	$target = yaamp_hashrate_constant();
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$user_rate = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and userid=$user->id");
	$user_bad = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and userid=$user->id");
	$percent = $user_rate? round($user_bad*100/$user_rate, 3): 0;

	$balance = bitcoinvaluetoa($user->balance);
	$paid = dboscalar("select sum(amount) from payouts where account_id=$user->id");
	$d = datetoa2($user->last_login);

	$miner_count = getdbocount('db_workers', "userid=$user->id");
	$block_count = getdbocount('db_blocks', "userid=$user->id");
	$block_diff = $paid? round(dboscalar("select sum(difficulty) from blocks where userid=$user->id")/$paid, 3): '?';

	$paid = bitcoinvaluetoa($paid);

	$user_rate = Itoa2($user_rate);
	$user_bad = Itoa2($user_bad);

	$coinimg = ''; $coinlink = '';
	$imgopt = array('width'=>'16');
	if ($coin && $user->coinid == $coin->id) {
		$coinimg = CHtml::image($coin->image, $coin->symbol, $imgopt);
		$coinlink = CHtml::link($coin->symbol, '/site/coin?id='.$coin->id);
	} else if ($user->coinid > 0) {
		$user_coin = getdbosql('db_coins', "id=:id", array(':id'=>$user->coinid));
		if ($user_coin) {
			$coinimg = CHtml::image($user_coin->image, $user_coin->symbol, $imgopt);
			$coinlink = CHtml::link($user_coin->symbol, '/site/coin?id='.$user_coin->id);
		}
	}

	echo "<tr class='ssrow'>";
	echo "<td width=24>$user->id</td>";
	echo "<td width=16>$coinimg</td>";
	echo "<td width=48><b>$coinlink</b></td>";
	echo '<td><a href="/?address='.$user->username.'"><b>'.$user->username.'</b></a></td>';
	echo "<td>$d</td>";
	echo "<td align=right>$miner_count</td>";

	echo "<td width=32 align=right>$user_rate</td>";
	echo "<td width=32 align=right>$user_bad</td>";

	if($percent > 50)
		echo "<td width=32><b>{$percent}%</b></td>";
	else
		echo "<td width=32>{$percent}%</td>";

	echo "<td align=right>$block_count</td>";
	echo "<td align=right>$block_diff</td>";
	echo "<td align=right>$balance</td>";
	echo "<td align=right>$paid</td>";

	echo "<td align=right><a href='/site/banuser?id=$user->id'><b>BAN</b></a></td>";
	echo "</tr>";

	$total_balance += $user->balance;
	$total_paid += $paid;
}

echo "</tbody>";

// totals colspan
$colspan = 8;

$total_balance = bitcoinvaluetoa($total_balance);
$total_paid = bitcoinvaluetoa($total_paid);
$user_count = count($users);

echo "<tr class='ssrow' style='border-top: 2px solid #eee;'>";
echo "<td colspan=3><b>Users Total ($user_count)</b></a></td>";
echo "<td colspan=$colspan></td>";
echo "<td align=right><b>$total_balance</b></td>";
echo "<td align=right><b>$total_paid</b></td>";
echo "<td></td>";
echo "</tr>";

if($coin)
{
	$balance = bitcoinvaluetoa($coin->balance);
	$profit = bitcoinvaluetoa($balance - $total_balance);

	echo "<tr class='ssrow' style='border-top: 2px solid #eee;'>";
	echo "<td colspan=3><b>Wallet Balance</b></a></td>";
	echo "<td colspan=$colspan></td>";
	echo "<td align=right><b>$balance</b></td>";
	echo "<td colspan=2></td>";
	echo "</tr>";

	echo "<tr class='ssrow' style='border-top: 2px solid #eee;'>";
	echo "<td colspan=3><b>Wallet Profit</b></a></td>";
	echo "<td colspan=$colspan></td>";
	echo "<td align=right><b>$profit</b></td>";
	echo "<td colspan=2></td>";
	echo "</tr>";
}

echo "</table>";

//echo "<p><a href='/site/bonususers'>1% bonus</a></p>";










