<?php

$id = getiparam('id');
$coin = getdbo('db_coins', $id);
if (!$coin) {
	$this->goback();
}

$this->pageTitle = 'Wallet - '.$coin->symbol;

$maxrows = arraySafeVal($_REQUEST,'rows',15);
$since = arraySafeVal($_REQUEST,'since',time()-(7*24*3600));

if (!empty($coin->algo) && $coin->algo != 'PoS')
	user()->setState('yaamp-algo', $coin->algo);

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
$info = $remote->getinfo();

$sellamount = $coin->balance;
//if ($info) $sellamount = floatval($sellamount) - arraySafeVal($info, "paytxfee") * 3;

echo getAdminSideBarLinks().'<br/><br/>';
echo getAdminWalletLinks($coin, $info, 'wallet');

echo '<br><div id="main_results"></div>';

// todo: use router createUrl
$url = '/site/coin?id='.$coin->id.'&since='.(time()-31*24*3600).'&rows='.($maxrows*2);
$moreurl = CHtml::link('Click here to show more transactions...', $url);

echo '<div class="loadfooter" style="margin-top: 8px;"><br/>'.$moreurl.'</div>';

echo '<div id="main_actions" style="margin-top: 8px;">';

/* 
echo "<br><a href='/site/makeconfigfile?id=$coin->id'><b>MAKE CONFIG & START</b></a>";

if($info)
{
	echo "<br><a href='/site/restartcoin?id=$coin->id'><b>RESTART COIND</b></a>";
	echo "<br><a href='/site/stopcoin?id=$coin->id'><b>STOP COIND</b></a>";

	if(isset($info['balance']) && $info['balance'] && !empty($coin->deposit_address))
		echo "<br><a href='javascript:showSellAmountDialog()'><b>SEND BALANCE TO</b></a> - $coin->deposit_address";
}
else
{
	echo "<br><a href='/site/startcoin?id=$coin->id'><b>START COIND</b></a>";
	echo "<br><br><a href='/site/resetblockchain?id=$coin->id'><b>RESET BLOCKCHAIN</b></a>";

	if($coin->installed)
		echo "<br><a href='javascript:uninstall_coin();'><b>UNINSTALL COIN</b></a><br>";
}

*/
echo <<<END

<br/><a class="red" href="/site/deleteearnings?id={$coin->id}"><b>DELETE EARNINGS</b></a>
<br/><a href="/site/clearearnings?id={$coin->id}"><b>CLEAR EARNINGS</b></a>
<br/><a href="/site/checkblocks?id={$coin->id}"><b>UPDATE BLOCKS</b></a>
<br/><a href="/site/payuserscoin?id={$coin->id}"><b>DO PAYMENTS</b></a>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

</div>
<style type="text/css">
loadfooter.main_actions { min-width: 200px; }
a.red { color: darkred; }
</style>

<script type="text/javascript">

function uninstall_coin()
{
	if(!confirm("Uninstall this coin?"))
		return;

	window.location.href = '/site/uninstallcoin?id=$coin->id';
}

var main_delay=30000;
var main_timeout;

function main_refresh()
{
	var url = "/site/coin_results?id={$id}&rows={$maxrows}&since={$since}";

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).error(main_error);
}

function main_ready(data)
{
	$('#main_results').html(data);
	main_timeout = setTimeout(main_refresh, main_delay);
	var sumHeight = 0 + $('#sums').height();
	if ($('#main_actions').height() < sumHeight) {
		$('#main_actions').height(sumHeight);
	}
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function showSellAmountDialog(marketid, marketname, address)
{
	$("#dlgaddr").html(address);
	$("#sell-amount-dialog").dialog(
	{
    	autoOpen: true,
		width: 400,
		height: 240,
		modal: true,
		title: 'Send $coin->symbol to '+marketname,

		buttons:
		{
			"Send / Sell": function()
			{
				amount = $('#input_sell_amount').val();
				window.location.href = '/market/sellto?id='+marketid+'&amount='+amount;
			},
		}
	});
	return false;
}

</script>

<div id="sell-amount-dialog" style="display: none;">
<br>
Address: <span id="dlgaddr">xxxxxxxxxxxx</span><br><br>
Amount: <input type=text id="input_sell_amount" value="$sellamount">
<br>
</div>

END;

JavascriptReady("main_refresh();");
