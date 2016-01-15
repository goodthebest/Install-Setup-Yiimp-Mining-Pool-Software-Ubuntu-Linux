<?php

function doCryptopiaTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

	$balances = getdbosql('db_balances', "name='cryptopia'");
	if(!$balances) return;

	$filter = array("Currency"=>"BTC");
	$query = cryptopia_api_user('GetBalance', $filter);

	if (is_object($query) && is_array($query->Data))
	foreach($query->Data as $balance)
	{
		if($balance->Symbol == 'BTC')
		{
			$balances->balance = $balance->Available;
			$balances->save();
			break;
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;

	// auto trade ... todo...
}
