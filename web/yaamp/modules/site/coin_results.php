<?php

$coin = getdbo('db_coins', getiparam('id'));
if (!$coin) $this->goback();

$PoS = ($coin->algo == 'PoS'); // or if 'stake' key is present in 'getinfo' method

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

$reserved1 = dboscalar("select sum(balance) from accounts where coinid=$coin->id");
$reserved2 = dboscalar("select sum(amount*price) from earnings
	where status!=2 and userid in (select id from accounts where coinid=$coin->id)");

$reserved = bitcoinvaluetoa(($reserved1 + $reserved2) * 2);
$reserved1 = altcoinvaluetoa($reserved1);
$reserved2 = bitcoinvaluetoa($reserved2);
$balance = altcoinvaluetoa($coin->balance);

$owed = dboscalar("select sum(amount) from earnings where status!=2 and coinid=$coin->id");
$owed_btc = $owed? bitcoinvaluetoa($owed*$coin->price): '';
$owed = $owed? altcoinvaluetoa($owed): '';

echo "cleared $reserved1, earnings $reserved2, reserved $reserved, balance $balance, owed $owed, owned btc $owed_btc<br><br>";

//////////////////////////////////////////////////////////////////////////////////////

$list = getdbolist('db_markets', "coinid=$coin->id order by price desc");

echo "<table class='dataGrid'>";
echo "<thead class=''>";

echo "<tr>";
echo "<th>Name</th>";
echo "<th>Price</th>";
echo "<th>Price2</th>";
echo "<th>Sent</th>";
echo "<th>Traded</th>";
echo "<th>Late</th>";
echo "<th>Deposit</th>";
echo "<th>Message</th>";
echo "</tr>";
echo "</thead><tbody>";

$bestmarket = getBestMarket($coin);
foreach($list as $market)
{
	$marketurl = '#';
	$price = bitcoinvaluetoa($market->price);
	$price2 = bitcoinvaluetoa($market->price2);

	$marketurl = getMarketUrl($coin, $market->name);

	if($bestmarket && $market->id == $bestmarket->id)
		echo "<tr class='ssrow' style='background-color: #dfd'>";
	else
		echo "<tr class='ssrow'>";

	echo "<td><b><a href='$marketurl' target=_blank>$market->name</a></b></td>";

	echo "<td>$price</td>";
	echo "<td>$price2</td>";

	$sent = datetoa2($market->lastsent);
	$traded = datetoa2($market->lasttraded);
	$late = $market->lastsent > $market->lasttraded ? 'late': '';

	echo '<td>'.(empty($sent)   ? "" : "$sent ago").'</td>';
	echo '<td>'.(empty($traded) ? "" : "$traded ago").'</td>';
	echo '<td>'.$late.'</td>';

	echo '<td>';
	echo "<a href='/market/update?id=$market->id'>edit</a> ";
	echo "<a href='javascript:showSellAmountDialog($market->id)'>sell</a> ";
	echo "<a href='/market/delete?id=$market->id'>del</a>";
	echo ' '.$market->deposit_address.'</td>';

	echo "<td>$market->message</td>";
	echo "</tr>";
}

echo "</tbody></table><br>";

//////////////////////////////////////////////////////////////////////////////////////

$info = $remote->getinfo();
if (!empty($info)) {
	$stake = isset($info['stake'])? $info['stake']: '';
	if ($stake !== '') $PoS = true;
}

echo "<table class='dataGrid'>";
//showTableSorter('maintable');
echo "<thead class=''>";

echo "<tr>";
echo "<th width=30></th>";
echo "<th width=30></th>";
echo "<th>Name</th>";
echo "<th>Symbol</th>";
echo "<th>Algo</th>";
echo "<th>Difficulty</th>";
echo "<th>Blocks</th>";
echo "<th>Balance</th>";
echo "<th>BTC</th>";
if ($PoS) echo "<th>Stake</th>";
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

echo '<td><b><a href="/site/coin?id='.$coin->id.'">'.$coin->name.'</a></b></td>';
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
$connections = isset($info['connections'])? $info['connections']: '';
$blocks = isset($info['blocks'])? $info['blocks']: '';

echo '<td>'.$coin->difficulty.'</td>';
if(!empty($errors))
	echo "<td style='color: red;' title='$errors'>$blocks</td>";
else
	echo "<td>$blocks</td>";

echo '<td>'.altcoinvaluetoa($balance).'</td>';

$btc = bitcoinvaluetoa($balance*$coin->price);
echo "<td>$btc</td>";
if ($PoS) echo '<td>'.$stake.'</td>';
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

echo <<<end
<style type="text/css">
tr.ssrow.orphan { color: darkred; }
</style>

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
<th>Tx(s)</th>
</tr>
</thead><tbody>
end;

// last week
$list_since = time()-(7*24*3600);

$txs = $remote->listtransactions('', 2500);

// to hide truncated days sums
$tx = reset($txs); $lastday = '';
if (count($txs) == 2500)
	$lastday = strftime('%F', $tx['time']);

$txs_array = array();
if (!empty($txs)) foreach($txs as $tx)
{
	if (intval($tx['time']) > $list_since)
		$txs_array[] = $tx;
}
krsort($txs_array);

$rows = 0;
foreach($txs_array as $tx)
{
	$block = null;
	if(isset($tx['blockhash']))
		$block = $remote->getblock($tx['blockhash']);

	$d = datetoa2($tx['time']);

	$category = $tx['category'];
	echo '<tr class="ssrow '.$category.'">';
	echo '<td><b>'.$d.'</b></td>';

	echo '<td>'.$category.'</td>';
	echo '<td>'.$tx['amount'].'</td>';

	if($block) {
		echo '<td>'.$block['height'].'</td><td>'.$block['difficulty'].'</td>';
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
		echo $address.'<br>';
	}
	echo '</td>';

	echo '<td>';
	if(!empty($block)) foreach($block['tx'] as $i => $txid) {
		$label = substr($txid, 0, 7);
		echo CHtml::link($label, '/explorer?id='.$coin->id.'&txid='.$txid, array('target'=>'_blank'));
		if (count($block['tx']) > 5) {
			echo '&nbsp;('.count($block['tx']).')';
			break;
		} else {
			echo '&nbsp;';
		}
	}
	echo '</td>';

	echo '</tr>';

	$rows++;
	if ($rows > 15) break;
}

echo '</tbody></table>';

//////////////////////////////////////////////////////////////////////////////////////

echo <<<end
<div id="sums" style="width: 400px; min-height: 250px; float: left; margin-top: 16px; margin-right: 16px;">
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

	$key = $day.' '.$tx['category'];
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

echo '</tbody></table></div>';

