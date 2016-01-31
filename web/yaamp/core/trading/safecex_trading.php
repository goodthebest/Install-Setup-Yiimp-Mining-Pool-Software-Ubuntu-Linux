<?php

function doSafecexTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

/*
	// to use when other coin balances will be required.
	$balances = safecex_api_user('getbalances');

	if(empty($balances)) return;

	$savebalance = getdbosql('db_balances', "name='safecex'");
	if (is_object($savebalance)) {
		$savebalance->balance = 0;

		if (is_array($balances)) foreach($balances as $balance)
		{
			if($balance->symbol == 'BTC') {
				$savebalance->balance = $balance->balance;
				$savebalance->save();
			}
		}
	}
*/

	// getbalance {"symbol":"BTC","balance":0.00118537,"pending":0,"orders":0.00029321,"total":0.00147858}

	$db_balance = getdbosql('db_balances', "name='safecex'");
	if (is_object($db_balance) && $db_balance->name=='safecex') {
		$balance = safecex_api_user('getbalance','&symbol=BTC');
		if (is_object($balance)) {
			$db_balance->balance = 0;
			if ($balance->symbol == 'BTC') {
				$db_balance->balance = $balance->balance;
				//$db_balance->onsell = $balance->orders;
				$db_balance->save();
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;

	// implement trade here...
}
