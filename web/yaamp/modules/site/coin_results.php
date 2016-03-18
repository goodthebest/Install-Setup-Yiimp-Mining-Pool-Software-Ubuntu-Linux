<?php

$coin = getdbo('db_coins', getiparam('id'));
if (!$coin) $this->goback();

$PoS = ($coin->algo == 'PoS'); // or if 'stake' key is present in 'getinfo' method

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
	echo ' <a style="color:darkred" title="Remove this market" href="/market/delete?id='.$market->id.'">x</a>';
	echo '</td>';

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

$account = '';
if ($coin->symbol == 'DCR') $account = '*';

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
if ($coin->symbol == 'DCR') {

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
		echo '&nbsp;('.count($block['tx']).')';
	}
	echo '</td>';

	echo '</tr>';

	$rows++;
	if ($rows >= $maxrows) break;
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

