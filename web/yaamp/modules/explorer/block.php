<?php

if (!$coin) return;

$this->pageTitle = $coin->name." block explorer";

$txid = getparam('txid', 'tssssssss');
if (empty($txid)) $txid = 'tssssssss'; // rmmm

echo <<<END
<script type="text/javascript">
function toggleRaw(el) {
	$(el).parents('tr').next('tr.raw').toggle();
}
$(function() {
	$('#favicon').remove();
	$('head').append('<link href="{$coin->image}" id="favicon" rel="shortcut icon">');
	$('span.txid').bind('click', function(el) { toggleRaw(el.target); });
	$('span.txid:contains("{$txid}")').css('color','darkred');
});
</script>

<style type="text/css">
span.monospace { font-family: monospace; }
span.txid { cursor: pointer; }
tr.raw td { overflow-x: scroll; max-width: 1880px; }
pre.json { font-size: 10px; }
.main-text-input { margin-top: 4px; margin-bottom: 4px; }
</style>
END;

function simplifyscript($script)
{
	$script = preg_replace("/[0-9a-f]+ OP_DROP ?/","", $script);
	$script = preg_replace("/OP_NOP ?/","", $script);
	return trim($script);
}

///////////////////////////////////////////////////////////////////////////////////////////////

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

$block = $remote->getblock($hash);
if(!$block) return;
//debuglog($block);

$d = date('Y-m-d H:i:s', $block['time']);
$confirms = isset($block['confirmations'])? $block['confirmations']: '';
$txcount = count($block['tx']);

$version = dechex($block['version']);
$nonce = dechex($block['nonce']);

echo '<table class="dataGrid1">';
echo '<tr><td width=100></td><td></td></tr>';

echo '<tr><td>Coin:</td><td><b>'.$coin->createExplorerLink($coin->name).'</b></td></tr>';
echo '<tr><td>Blockhash:</td><td><span class="txid monospace">'.$hash.'</span></td></tr>';

echo '</tr><tr class="raw" style="display:none;"><td colspan="2"><pre class="json">';
echo json_encode($block, 128);
echo '</pre></td>';

echo '<tr><td>Confirmations:</td><td>'.$confirms.'</td></tr>';
echo '<tr><td>Height:</td><td>'.$block['height'].'</td></tr>';
echo '<tr><td>Time:</td><td>'.$d.'</td></tr>';
echo '<tr><td>Difficulty:</td><td>'.$block['difficulty'].'</td></tr>';
echo '<tr><td>Bits:</td><td><span class="monospace">'.$block['bits'].'</span></td></tr>';
echo '<tr><td>Nonce:</td><td><span class="monospace">'.$nonce.'</span></td></tr>';
echo '<tr><td>Version:</td><td><span class="monospace">'.$version.'</span></td></tr>';
echo '<tr><td>Size:</td><td>'.$block['size'].' bytes</td></tr>';

if(isset($block['flags']))
	echo '<tr><td>Flags:</td><td><span class="monospace">'.$block['flags'].'</span></td></tr>';

if(isset($block['previousblockhash']))
	echo '<tr><td>Previous Hash:</td><td><span class="monospace">'.
		$coin->createExplorerLink($block['previousblockhash'], array('hash'=>$block['previousblockhash'])).
	'</span></td></tr>';

if(isset($block['nextblockhash']))
	echo '<tr><td>Next Hash:</td><td><span class="monospace">'.
		$coin->createExplorerLink($block['nextblockhash'], array('hash'=>$block['nextblockhash'])).
	'</span></td></tr>';

echo '<tr><td>Merkle Root:</td><td><span class="monospace">'.$block['merkleroot'].'</span></td></tr>';

echo '<tr><td>Transactions:</td><td>'.$txcount.'</td></tr>';

echo "</table><br>";

////////////////////////////////////////////////////////////////////////////////

echo <<<end

<table class="dataGrid">
<thead>
<tr>
<th>Transaction Hash</th>
<th>Value</th>
<th>From</th>
<th>To (amount)</th>
</tr>
</thead>
end;

foreach($block['tx'] as $txhash)
{
	$tx = $remote->getrawtransaction($txhash, 1);
	if(!$tx) continue;

	$valuetx = 0;
	foreach($tx['vout'] as $vout)
		$valuetx += $vout['value'];

	echo '<tr class="ssrow">';
	echo '<td><span class="txid monospace">'.$tx['txid'].'</span></td>';
	echo "<td>$valuetx</td>";

	echo "<td>";
	foreach($tx['vin'] as $vin) {
		if(isset($vin['coinbase']))
			echo "Generation";
	}
	echo "</td>";

	echo "<td>";
	foreach($tx['vout'] as $vout)
	{
		$value = $vout['value'];
		if ($value == 0) continue;

		if(isset($vout['scriptPubKey']['addresses'][0]))
			echo '<span class="monospace">'.$vout['scriptPubKey']['addresses'][0]."</span> ($value)";
		else
			echo "($value)";

		echo '<br>';
	}
	echo "</td>";

	echo '</tr><tr class="raw" style="display:none;"><td colspan="4"><pre class="json">';
	unset($tx['hex']);
	echo json_encode($tx, 128);
	echo '</pre></td>';

	echo "</tr>";
}

if ($coin->rpcencoding == 'DCR' && isset($block['stx'])) {

	echo '<tr><th class="section" colspan="4">';
	echo '<b>Stake</b>';
	echo '</th></tr>';

	foreach($block['stx'] as $txhash)
	{
		$stx = $remote->getrawtransaction($txhash, 1);
		if(!$stx) continue;

		$valuetx = 0;
		foreach($stx['vout'] as $vout)
			$valuetx += $vout['value'];

		echo '<tr class="ssrow">';
		echo '<td><span class="txid monospace">'.$stx['txid'].'</span></td>';
		echo "<td>$valuetx</td>";

		echo "<td>";
		if(isset($stx['vout'][0]['scriptPubKey']) && arraySafeVal($stx['vout'][0]['scriptPubKey'],'type') == 'stakesubmission')
			echo "Ticket";
		else foreach($stx['vin'] as $vin) {
			if (arraySafeVal($vin,'blockheight') > 0) {
				echo $coin->createExplorerLink($vin['blockheight'], array('height'=>$vin['blockheight']));
				echo '<br/>';
			}
		}
		echo "</td>";

		echo "<td>";
		foreach($stx['vout'] as $vout)
		{
			$value = $vout['value'];
			if ($value == 0) continue;

			if(isset($vout['scriptPubKey']['addresses'][0]))
				echo '<span class="monospace">'.$vout['scriptPubKey']['addresses'][0]."</span> ($value)";
			else
				echo "($value)";

			echo '<br/>';
		}
		echo "</td>";

		echo '</tr><tr class="raw" style="display:none;"><td colspan="4"><pre class="json">';
		unset($stx['hex']);
		echo json_encode($stx, 128);
		echo '</pre></td>';

		echo '</tr>';
	}
}

echo '</table>';

$actionUrl = $coin->visible ? '/explorer/'.$coin->symbol : '/explorer/search?id='.$coin->id;

echo <<<end
<form action="{$actionUrl}" method="POST" style="padding: 8px; padding-left: 0px;">
<input type="text" name="height" class="main-text-input" placeholder="block height" style="width: 80px;">
<input type="text" name="txid" class="main-text-input" placeholder="tx hash" style="width: 450px; margin: 4px;">
<input type="submit" value="Search" class="main-submit-button">
</form>
end;

echo '<br><br><br><br><br><br><br><br><br><br>';
echo '<br><br><br><br><br><br><br><br><br><br>';
echo '<br><br><br><br><br><br><br><br><br><br>';


