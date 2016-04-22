<?php

if (!$coin) $this->goback();
$DCR = ($coin->rpcencoding == 'DCR');

if (!$DCR) $this->goback();

$this->pageTitle = 'Tickets - '.$coin->symbol;

// last week
$list_since = arraySafeVal($_GET,'since',time()-(7*24*3600));

$maxrows = arraySafeVal($_GET,'rows', 100);
$maxrows = min($maxrows, 2500);

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
$info = $remote->getinfo();
$stakeinfo = $remote->getstakeinfo();
$locked = $remote->getbalance('*',0,'locked');

echo getAdminSideBarLinks().'<br/><br/>';
echo getAdminWalletLinks($coin, $info, 'tickets').'<br/><br/>';

//////////////////////////////////////////////////////////////////////////////////////

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

echo <<<end
<style type="text/css">
td.missed { color: darkred; }
tr.voted { color: darkgreen; }
div.balance { text-align: right; height: 30px; width: 200px; float: right; margin-top: -80px; margin-bottom: 16px; }
div.form { text-align: right; height: 30px; width: 350px; float: right; margin-top: -48px; margin-bottom: 16px; margin-right: -8px; }
.main-submit-button { cursor: pointer; }
</style>

<div class="balance" style="display: block;">
Stake: </b>{$locked} {$coin->symbol}<br/>
Balance: </b>{$remote->getbalance()} {$coin->symbol}<br/>
</div>

<div class="form">
<form action="/site/ticketBuy?id={$coin->id}" method="post" style="padding: 8px;">
<input type="text" name="spendlimit" class="main-text-input" placeholder="Spend limit" autocomplete="off" style="width: 80px; margin-right: 4px;">
<input type="text" name="quantity" class="main-text-input" placeholder="Quantity" style="width: 60px; margin-right: 4px;">
<input type="submit" value="Buy" class="main-submit-button" >
</form>
</div>

end;

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	widgets: ['zebra','Storage','saveSort'],
	widgetOptions: {
		saveSort: true
	}
}");

echo <<<end
<table class="dataGrid">
<thead class="">
<tr>
<th>Time</th>
<th>Category</th>
<th>Amount</th>
<th>Stake</th>
<th>Height</th>
<th>Confirm</th>
<th>Fees</th>
<th>Tx id</th>
</tr>
</thead><tbody>
end;

$account = $DCR ? '*' : '';

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

$voted_txs = array();
$list_txs = array();
$tickets = $remote->gettickets(true);

// extract  stxs from decred transactions
if (!empty($txs_array)) {

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
			$stx = $remote->getrawtransaction($tx['txid'], 1);

			// voted (listed twice ? in listtransactions)
			if ($tx['vout'] > 0) {
				if (in_array($tx['txid'], $list_txs)) continue; // dup
				$category = 'ticket';
				// ticket price
				if ($stx && isset($stx['vin'][0])) {
					$txs_array[$key]['input'] = $stx['vin'][0]['amountin'] * 0.00000001;
				}
				$list_txs[] = $tx['txid'];
			} else {
				$category = 'stake';
				if ($stx && isset($stx['vin'][0])) {
					// won ticket value
					$txs_array[$key]['amount'] = $stx['vin'][0]['amountin'] * 0.00000001;
				}
				if ($stx && isset($stx['vin'][1])) {
					$voted_txs[] = $stx['vin'][1]['txid'];
					$list_txs[] = $stx['vin'][1]['txid'];
				}
			}

			$txs_array[$key]['category'] = $category;
			$txs_array[$key]['stx'] = $stx;

		}
		else if ($category == 'receive') {
			$prev_tx = $tx;
		}
		// for truncated day sums
		if ($lastday == '' && count($txs) == 2500)
			$lastday = strftime('%F', $tx['time']);
	}
	ksort($txs_array);
}

if (!empty($tickets)) foreach ($tickets['hashes'] as $n => $txid) {
	if (!in_array($txid, $list_txs)) {
		$stx = $remote->getrawtransaction($txid, 1);
		$k = time() - $stx['time'] + $n; // sort key
		$stx['category'] = 'ticket';
		if (isset($stx['vin'][0]))
			$stx['input'] = $stx['vin'][0]['amountin'] * 0.00000001;
		$commitamt = 0.;
		foreach ($stx['vout'] as $v) {
			if (arraySafeVal($v['scriptPubKey'],'type') == 'sstxcommitment')
				$commitamt += $v['scriptPubKey']['commitamt'];
		}
		if ($commitamt && isset($stx['vout'][0])) {
			$stx['fee'] = round($commitamt - $stx['vout'][0]['value'], 4);
		}
		$stx['stx'] = $stx;
		$txs_array[$k] = $stx;
	}
	ksort($txs_array);
}

$rows = 0;
foreach($txs_array as $tx)
{
	$category = arraySafeVal($tx, 'category');

	if ($category != 'stake' && $category != 'ticket') continue;

	$conf = arraySafeVal($tx,'confirmations');
	if ($category == 'stake' && $conf < 256) $category = 'immature';

	$stx = arraySafeVal($tx,'stx');
	$stake = ''; $amount = '';
	if ($category == 'ticket') {
		$stake = arraySafeVal($stx, 'stake', $stx['vout'][0]['value']);
		if ($stake === 0) continue;
		if (in_array($tx['txid'], $voted_txs)) $category = 'voted';
		else if (!in_array($tx['txid'], $tickets['hashes'])) $category = 'missed';
	} else {
		$amount = (double) arraySafeVal($tx,'amount');
		$stake = $amount - $stx['vout'][2]['value'];
	}

	$block = null;
	if(isset($tx['blockhash']))
		$block = $remote->getblock($tx['blockhash']);

	echo '<tr class="ssrow '.$category.'">';

	$d = datetoa2($tx['time']);
	echo '<td><b>'.$d.'</b></td>';

	echo '<td>'.$category.'</td>';
	echo '<td>'.$amount.'</td>';
	echo '<td>'.$stake.'</td>';

	if($block)
		echo '<td>'.$block['height'].'</td>';
	else
		echo '<td></td>';

	echo '<td>'.$conf.'</td>';

	echo '<td>'.abs(arraySafeVal($tx,'fee')).'</td>';

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

echo '</tbody></table><br>';

echo '<b>Ticket price: </b>'.$stakeinfo['difficulty'].' + '.$remote->getticketfee().' '.$coin->symbol.'/kB<br/>';
echo '<b>Tickets: </b>'.$stakeinfo['live'];
if ($stakeinfo['immature']) echo ' + '.$stakeinfo['immature'].' immature';
if ($stakeinfo['ownmempooltix']) echo ' + '.$stakeinfo['ownmempooltix'].' purchased';
echo '<br/>';
echo '<b>Total won: </b>'.$stakeinfo['totalsubsidy'].' '.$coin->symbol.' ('.$stakeinfo['voted'].')<br/>';
if ($stakeinfo['missed']) echo '<b>Missed: </b>'.$stakeinfo['missed'].'<br/>';

$staking = $remote->getgenerate() > 0 ? 'enabled' : 'disabled';
echo '<br/>';
echo '<b>Staking: </b>'.$staking.'<br/>';
echo '<b>Auto buy ticket(s) < '.$remote->getticketmaxprice().' '.$coin->symbol.'</b><br/>';

echo '<pre>';
//echo json_encode($stakeinfo, 128);
echo '</pre>';
