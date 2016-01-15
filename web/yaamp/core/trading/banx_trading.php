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
		if($balance->currency == 'BTC') {
			$savebalance->balance = $balance->available;
			$savebalance->save();
			break;
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;
}
