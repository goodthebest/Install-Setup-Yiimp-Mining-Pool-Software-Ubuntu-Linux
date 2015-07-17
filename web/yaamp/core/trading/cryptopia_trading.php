<?php

function doCryptopiaTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

	$balance = getdbosql('db_balances', "name='cryptopia'");
	if(!$balance) return;

	// not available yet...

	if (!YAAMP_ALLOW_EXCHANGE) return;

	// auto trade ... todo...
}
