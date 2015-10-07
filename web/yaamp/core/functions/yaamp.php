<?php

function yaamp_get_algos()
{
	/* Toggle Site Algos Here */
	return array(
		'sha256',
		'scrypt',
		'scryptn',
		'blake',
		'keccak',
		'luffa',
		'lyra2',
		'lyra2v2',
		'neoscrypt',
		'nist5',
		'penta',
		'quark',
		'qubit',
		'c11',
		'x11',
		'x13',
		'x15',
		'groestl', // dmd-gr -m 256 (deprecated)
		'dmd-gr',
		'myr-gr',
		'm7m',
		'sib',
		'skein',
		'skein2',
		'zr5',
	);
}

// mBTC coef per algo
function yaamp_get_algo_norm($algo)
{
	global $configAlgoNormCoef;
	if (isset($configAlgoNormCoef[$algo]))
		return (float) $configAlgoNormCoef[$algo];

	$a = array(
		'sha256'	=> 1.0,
		'scrypt'	=> 1.0,
		'scryptn'	=> 1.0,
		'x11'		=> 1.0,
		'x13'		=> 1.0,
		'zr5'		=> 1.0,
		'nist5'		=> 1.0,
		'neoscrypt'	=> 1.0,
		'lyra2'		=> 1.0,
		'lyra2v2'	=> 1.0,
		'quark'		=> 1.0,
		'fresh'		=> 1.0,
		'qubit'		=> 1.0,
		'skein'		=> 1.0,
		'groestl'	=> 1.0,
		'blake'		=> 1.0,
		'keccak'	=> 1.0,
		'skein2'	=> 1.0,
	);

	if(!isset($a[$algo]))
		return 1.0;

	return $a[$algo];
}

function getAlgoColors($algo)
{
	$a = array(
		'sha256'	=> '#d0d0a0',
		'scrypt'	=> '#c0c0e0',
		'neoscrypt'	=> '#a0d0f0',
		'scryptn'	=> '#d0d0d0',
		'c11'		=> '#a0a0d0',
		'x11'		=> '#f0f0a0',
		'x13'		=> '#d0f0c0',
		'x14'		=> '#a0f0c0',
		'x15'		=> '#f0b0a0',
		'blake'		=> '#f0f0f0',
		'groestl'	=> '#d0a0a0',
		'dmd-gr'	=> '#a0c0f0',
		'myr-gr'	=> '#a0c0f0',
		'keccak'	=> '#c0f0c0',
		'luffa'		=> '#a0c0c0',
		'm7m'		=> '#d0a0a0',
		'penta'		=> '#80c0c0',
		'nist5'		=> '#e0d0e0',
		'quark'		=> '#c0c0c0',
		'qubit'		=> '#d0a0f0',
		'lyra2'		=> '#80a0f0',
		'lyra2v2'	=> '#80c0f0',
		'sib'		=> '#a0a0c0',
		'skein'		=> '#80a0a0',
		'skein2'	=> '#a0a0a0',
		'zr5'		=> '#d0b0d0',

		'MN'		=> '#ffffff', // MasterNode Earnings
		'PoS'		=> '#ffffff'  // Stake
	);

	if(!isset($a[$algo]))
		return '#ffffff';

	return $a[$algo];
}

function getAlgoPort($algo)
{
	$a = array(
		'sha256'	=> 3333,
		'scrypt'	=> 3433,
		'c11'		=> 3573,
		'x11'		=> 3533,
		'x13'		=> 3633,
		'x15'		=> 3733,
		'nist5'		=> 3833,
		'x14'		=> 3933,
		'quark'		=> 4033,
		'fresh'		=> 4133,
		'neoscrypt'	=> 4233,
		'scryptn'	=> 4333,
		'lyra2'		=> 4433,
		'lyra2v2'	=> 4533,
		'jha'		=> 4633,
		'qubit'		=> 4733,
		'zr5'		=> 4833,
		'skein'		=> 4933,
		'sib'		=> 5033,
		'keccak'	=> 5133,
		'skein2'	=> 5233,
		//'groestl'	=> 5333,
		'dmd-gr'	=> 5333,
		//'myr-gr'	=> 5433,
		'whirlpool'	=> 5433,
		'zr5'		=> 5533,
		// 5555 to 5683 reserved
		'blake'		=> 5733,
		'penta'		=> 5833,
		'luffa'		=> 5933,
		'm7m'		=> 6033,
	);

	global $configCustomPorts;
	if(isset($configCustomPorts[$algo]))
		return $configCustomPorts[$algo];

	if(!isset($a[$algo]))
		return 3033;

	return $a[$algo];
}

////////////////////////////////////////////////////////////////////////

function yaamp_fee($algo)
{
	$fee = controller()->memcache->get("yaamp_fee-$algo");
	if($fee) return $fee;

	$norm = yaamp_get_algo_norm($algo);
	if($norm == 0) $norm = 1;

	$hashrate = getdbosql('db_hashrate', "algo=:algo order by time desc", array(':algo'=>$algo));
	if(!$hashrate || !$hashrate->difficulty) return 1;

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_coinonly-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and algo=:algo and jobid=0", array(':algo'=>$algo));

//	$fee = round(log($hashrate->hashrate * $norm / 1000000 / $hashrate->difficulty + 1), 1) + YAAMP_FEES_MINING;
//	$fee = round(log($rate * $norm / 2000000 / $hashrate->difficulty + 1), 1) + YAAMP_FEES_MINING;
	$fee = YAAMP_FEES_MINING;

	// local fees config
	global $configFixedPoolFees;
	if (isset($configFixedPoolFees[$algo])) {
		$fee = (float) $configFixedPoolFees[$algo];
	}

	controller()->memcache->set("yaamp_fee-$algo", $fee);
	return $fee;
}

function take_yaamp_fee($v, $algo)
{
	return $v - ($v * yaamp_fee($algo) / 100);
}

function yaamp_hashrate_constant($algo=null)
{
	return pow(2, 42);		// 0x400 00000000
}

function yaamp_hashrate_step()
{
	return 300;
}

function yaamp_profitability($coin)
{
	if(!$coin->difficulty) return 0;

	$btcmhd = 20116.56761169 / $coin->difficulty * $coin->reward * $coin->price;
	if(!$coin->auxpow && $coin->rpcencoding == 'POW')
	{
		$listaux = getdbolist('db_coins', "enable and visible and auto_ready and auxpow and algo='$coin->algo'");
		foreach($listaux as $aux)
		{
			if(!$aux->difficulty) continue;

			$btcmhdaux = 20116.56761169 / $aux->difficulty * $aux->reward * $aux->price;
			$btcmhd += $btcmhdaux;
		}
	}

	if($coin->algo == 'sha256') $btcmhd *= 1000;
	return $btcmhd;
}

function yaamp_convert_amount_user($coin, $amount, $user)
{
	$refcoin = getdbo('db_coins', $user->coinid);
	if(!$refcoin && YAAMP_ALLOW_EXCHANGE) $refcoin = getdbosql('db_coins', "symbol='BTC'");
	if(!$refcoin || $refcoin->price2<=0) return 0;

	$value = $amount * $coin->price2 / $refcoin->price2;
	return $value;
}

function yaamp_convert_earnings_user($user, $status)
{
	$refcoin = getdbo('db_coins', $user->coinid);
	if(!$refcoin && YAAMP_ALLOW_EXCHANGE) $refcoin = getdbosql('db_coins', "symbol='BTC'");
	if(!$refcoin || $refcoin->price2<=0) return 0;

	$value = dboscalar("select sum(amount*price) from earnings where $status and userid=$user->id");
	$value = $value/$refcoin->price2;

	return $value;
}

////////////////////////////////////////////////////////////////////////////////////////////

function yaamp_pool_rate($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_pool_rate_bad($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_bad-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_pool_rate_rentable($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_rentable-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and extranonce1 and time>$delay and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_user_rate($userid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_user_rate-$userid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and userid=$userid and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_user_rate_bad($userid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_user_rate_bad-$userid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and userid=$userid and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_worker_rate($workerid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_worker_rate-$workerid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and workerid=$workerid");

	return $rate;
}

function yaamp_worker_rate_bad($workerid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_worker_rate_bad-$workerid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and workerid=$workerid");

	return empty($rate)? 0: $rate;
}

function yaamp_coin_rate($coinid)
{
	$coin = getdbo('db_coins', $coinid);
	if(!$coin || !$coin->enable) return 0;

	$target = yaamp_hashrate_constant($coin->algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_coin_rate-$coinid",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and coinid=$coinid");

	return $rate;
}

function yaamp_rented_rate($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_rented_rate-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where time>$delay and algo=:algo and jobid!=0 and valid", array(':algo'=>$algo));

	return $rate;
}

function yaamp_job_rate($jobid)
{
	$job = getdbo('db_jobs', $jobid);
	if(!$job) return 0;

	$target = yaamp_hashrate_constant($job->algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_job_rate-$jobid",
		"select sum(difficulty) * $target / $interval / 1000 from jobsubmits where valid and time>$delay and jobid=$jobid");
	return $rate;
}

function yaamp_job_rate_bad($jobid)
{
	$job = getdbo('db_jobs', $jobid);
	if(!$job) return 0;

	$target = yaamp_hashrate_constant($job->algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_job_rate_bad-$jobid",
		"select sum(difficulty) * $target / $interval / 1000 from jobsubmits where not valid and time>$delay and jobid=$jobid");

	return $rate;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////

function yaamp_pool_rate_pow($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_pow-$algo",
		"select sum(shares.difficulty) * $target / $interval / 1000 from shares, coins
			where shares.valid and shares.time>$delay and shares.algo=:algo and
			shares.coinid=coins.id and coins.rpcencoding='POW'", array(':algo'=>$algo));

	return $rate;
}

/////////////////////////////////////////////////////////////////////////////////////////////

function yaamp_renter_account($renter)
{
	if(YAAMP_PRODUCTION)
		return "renter-prod-$renter->id";
	else
		return "renter-dev-$renter->id";
}


/////////////////////////////////////////////////////////////////////////////////////////////

function getAdminSideBarLinks()
{
$links = <<<end
<a href="/site/exchange">Exchanges</a>&nbsp;
<a href="/site/user">Users</a>&nbsp;
<a href="/site/worker">Workers</a>&nbsp;
<a href="/site/version">Version</a>&nbsp;
<a href="/site/earning">Earnings</a>&nbsp;
<a href="/site/payments">Payments</a>&nbsp;
<a href="/site/monsters">Big Miners</a>&nbsp;
end;
	return $links;
}
