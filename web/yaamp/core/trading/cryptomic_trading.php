<?php

function doCryptomicTrading($quick=false)
{
	$exchange = 'cryptomic';
	$updatebalances = true;

	if (exchange_get($exchange, 'disabled')) return;

	// [{"currency":"BTC","balance":0.02265703,"available":0.02265703,"pending":0,"isbts":0,"cryptoaddress":"1DCVPWgs..."}]}
	$balances = cryptomic_api_user('account/getbalances');
	if(!$balances || !isset($balances->result) || !$balances->success) return;

	$savebalance = getdbosql('db_balances', "name IN ('{$exchange}','banx')");
	if (is_object($savebalance)) {
		$savebalance->balance = 0;
		$savebalance->save();

		dborun("UPDATE markets SET balance=0 WHERE name='{$exchange}'");
	}

	foreach($balances->result as $balance)
	{
		if ($balance->currency == 'BTC') {
			if (!is_object($savebalance)) continue;
			$savebalance->balance = $balance->available;
			$savebalance->save();
			continue;
		}

		if ($updatebalances) {
			// store available balance in market table
			$coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
				array(':symbol'=>$balance->currency)
			);
			if (empty($coins)) continue;
			foreach ($coins as $coin) {
				$market = getdbosql('db_markets', "coinid=:coinid AND name='{$exchange}'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				$market->balance = $balance->available;
				$market->ontrade = $balance->balance - $balance->available;
				$deposit_address = objSafeVal($balance,'cryptoaddress');
				if (!empty($deposit_address) && $market->deposit_address != $balance->cryptoaddress) {
					debuglog("{$exchange}: {$coin->symbol} deposit address updated");
					$market->deposit_address = $balance->cryptoaddress;
				}
				$market->balancetime = time();
				$market->save();
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;

	$flushall = rand(0, 8) == 0;
	if($quick) $flushall = false;
}
