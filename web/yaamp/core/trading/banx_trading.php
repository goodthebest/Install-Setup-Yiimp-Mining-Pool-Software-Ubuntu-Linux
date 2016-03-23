<?php

function doBanxTrading($quick=false)
{
	$exchange = 'banx';

	// [{"currency":"BTC","balance":0.02265703,"available":0.02265703,"pending":0,"isbts":0,"cryptoaddress":"1DCVPWgs..."}]}
	$balances = banx_api_user('account/getbalances');
	if(!$balances || !isset($balances->result) || !$balances->success) return;

	foreach($balances->result as $balance)
	{
		if ($balance->currency == 'BTC') {

			$savebalance = getdbosql('db_balances', "name='{$exchange}'");
			if (!is_object($savebalance)) continue;

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
				$market = getdbosql('db_markets', "coinid=:coinid AND name='{$exchange}'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				if ($market->balance != $balance->available) {
					$market->balance = $balance->available;
					if (!empty($balance->cryptoaddress) && $market->deposit_address != $balance->cryptoaddress) {
						debuglog("{$exchange}: {$coin->symbol} deposit address updated");
						$market->deposit_address = $balance->cryptoaddress;
					}
					$market->save();
				}
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;

	$flushall = rand(0, 8) == 0;
	if($quick) $flushall = false;
}
