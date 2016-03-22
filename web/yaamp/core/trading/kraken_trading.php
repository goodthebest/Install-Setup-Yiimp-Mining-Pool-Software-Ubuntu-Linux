<?php

function doKrakenTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

	$balances = kraken_api_user('Balance');
	if(!$balances || !is_array($balances)) return;

	$savebalance = getdbosql('db_balances', "name='kraken'");
	if (!is_object($savebalance)) return;

	$savebalance->balance = 0;

	foreach($balances as $symbol => $balance)
	{
		if ($symbol == 'BTC') {
			$savebalance->balance = $balance;
			$savebalance->save();
			continue;
		}

		if (!YAAMP_ALLOW_EXCHANGE) {
			// store available balance in market table
			$coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
				array(':symbol'=>$symbol)
			);
			if (empty($coins)) continue;
			foreach ($coins as $coin) {
				$market = getdbosql('db_markets', "coinid=:coinid AND name='kraken'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				if ($market->balance != $balance) {
					$market->balance = $balance;
					$market->save();
				}
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;
}
