<?php

function doSafecexTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

	$balances = safecex_api_user('getbalances'); //,'&symbol=BTC');

	if(empty($balances)) return;

	$savebalance = getdbosql('db_balances', "name='safecex'");
	if (is_object($savebalance)) {
		$savebalance->balance = 0;

		if (is_array($balances)) foreach($balances as $balance)
		{
			if($balance->symbol == 'BTC') {
				$savebalance->balance = $balance->balance;
				$savebalance->save();
				break;
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;

	// implement trade here...
}
