<?php

// markets : https://coinsmarkets.com/apicoin.php

function coinsmarkets_api_query($method)
{
	$uri = "https://coinsmarkets.com/{$method}.php";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_SESSIONID_CACHE, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

	$res= curl_exec($ch);
	$obj = json_decode($res, true);

	return $obj;
}

function coinsmarkets_api_user($method, $params=array())
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_COINMARKETS_SECRET')) define('EXCH_COINMARKETS_SECRET', '');

	if (empty(EXCH_COINMARKETS_SECRET)) return false;

	// todo: see https://coinsmarkets.com/tradeapi.php

	return false;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////

// manual update of one market
function coinsmarkets_update_market($market)
{
	$exchange = 'coinsmarkets';
	if (is_string($market))
	{
		$symbol = $market;
		$coin = getdbosql('db_coins', "symbol=:sym", array(':sym'=>$symbol));
		if(!$coin) return false;
		$pair = 'BTC_'.strtoupper($symbol);
		$market = getdbosql('db_markets', "coinid={$coin->id} AND name='$exchange'");
		if(!$market) return false;

	} else if (is_object($market)) {

		$coin = getdbo('db_coins', $market->coinid);
		if(!$coin) return false;
		$symbol = $coin->getOfficialSymbol();
		$pair = 'BTC_'.strtoupper($symbol);
		if (!empty($market->base_coin)) $pair = $market->base_coin.'_'.strtoupper($symbol);
	}

	$t1 = microtime(true);
	$m = coinsmarkets_api_query('apicoin');
	if(!is_array($m) || empty($m)) return false;
	foreach ($m as $p=>$data) {
		if ($p == $pair) {
			$ticker = $data;
			break;
		}
	}
	if(!isset($ticker)) return false;

	$price2 = ((double)$ticker['highestBid'] + (double)$ticker['lowestAsk'])/2;
	$market->price = AverageIncrement($market->price, $ticker['highestBid']);
	$market->price2 = AverageIncrement($market->price2, $price2);
	$market->pricetime = time();
	$market->save();

	$apims = round((microtime(true) - $t1)*1000,3);
	user()->setFlash('message', "$exchange $symbol price updated in $apims ms");

	return true;
}
