<?php

function doEmpoexTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

	$balances = empoex_api_user('account/balance','BTC');
	// {"available":[{"Coin":"BTC","Amount":"0.00102293"}],"pending":[],"held":[]}

        if(!$balances || !isset($balances->available) || empty($balances->available)) return;

	$savebalance = getdbosql('db_balances', "name='empoex'");
	$savebalance->balance = 0;

	foreach($balances->available as $balance)
	{
		if($balance->Coin == 'BTC') {
			$savebalance->balance = $balance->Amount;
			$savebalance->save();
			break;
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;
}
