<?php

function doBanxTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

	$balances = banx_api_user('account/getbalances');
	if(!$balances || !isset($balances->result) || !$balances->success) return;

	$savebalance = getdbosql('db_balances', "name='banx'");
	if (!is_object($savebalance)) return;

	$savebalance->balance = 0;

	foreach($balances->result as $balance)
	{
		if ($balance->currency == 'BTC') {
			$savebalance->balance = $balance->available;
			$savebalance->save();
			continue;
		}

		if (!YAAMP_ALLOW_EXCHANGE) {
			// store available balance in market table
			$coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
				array(':symbol'=>$balance->currency)
			);
			if (empty($coins)) continue;
			foreach ($coins as $coin) {
				$market = getdbosql('db_markets', "coinid=:coinid AND name='banx'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				if ($market->balance != $balance->available) {
					$market->balance = $balance->available;
					$market->save();
				}
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;
}
