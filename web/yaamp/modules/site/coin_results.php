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
<div id="markets">
<table class="dataGrid">
<thead><tr>
<th width="100">Market</th>
<th width="100">Bid</th>
<th width="100">Ask</th>
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

$list = getdbolist('db_markets', "coinid={$coin->id} ORDER BY disabled, priority, price DESC");

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
	echo '&nbsp;<a class="red" title="Remove this market" href="/market/delete?id='.$market->id.'">delete</a>';
	echo '</td>';

	echo "</tr>";
}

echo "</tbody></table></div>";

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

echo '<table id="infos" class="dataGrid">';
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
	echo '<td class="red" title="'.$errors.'">'.$blocks.'</td>';
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

//////////////////////////////////////////////////////////////////////////////////////

// last week
$list_since = arraySafeVal($_GET,'since',time()-(7*24*3600));

$maxrows = arraySafeVal($_GET,'rows', 200);
$maxrows = min($maxrows, 2500);

echo <<<end
<div id="transactions">
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

$url = '/site/coin?id='.$coin->id.'&since='.(time()-31*24*3600).'&rows='.($maxrows*2);
$moreurl = CHtml::link('Click here to show more transactions...', $url);

echo '<div class="loadfooter" style="margin-top: 4px;">'.$moreurl.'</div>';
echo '</div>';

//////////////////////////////////////////////////////////////////////////////////////

echo <<<end
<div id="sums">
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

if (strpos(YIIMP_WATCH_CURRENCIES, $coin->symbol) !== false) {
	$this->renderPartial('coin_market_graph', array('coin'=>$coin));
}
