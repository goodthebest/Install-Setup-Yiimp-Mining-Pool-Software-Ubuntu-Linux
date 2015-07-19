<?php

function BackendUsersUpdate()
{
	$t1 = microtime(true);

	$list = getdbolist('db_accounts', "coinid IS NULL OR IFNULL(coinsymbol,'') != ''");
	foreach($list as $user)
	{
	//	debuglog("testing user $user->username, $user->coinsymbol");
		if(!empty($user->coinsymbol))
		{
			$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$user->coinsymbol));
			$user->coinsymbol = '';

			if($coin)
			{
				if($user->coinid == $coin->id)
				{
					$user->save();
					continue;
				}

				$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

				$b = $remote->validateaddress($user->username);
				if($b && isset($b['isvalid']) && $b['isvalid'])
				{
					if($user->balance > 0)
					{
						$coinref = getdbo('db_coins', $user->coinid);
						if(!$coinref) {
							if (YAAMP_ALLOW_EXCHANGE)
								$coinref = getdbosql('db_coins', "symbol='BTC'");
							else
								continue;
						}

						$user->balance = $user->balance*$coinref->price/$coin->price;
					}

					$user->coinid = $coin->id;
					$user->save();

					debuglog("$user->username set to $coin->symbol, balance $user->balance");
					continue;
				}
			}
		}

		$user->coinid = 0;

		$coins = getdbolist('db_coins', "enable ORDER BY difficulty DESC");
		foreach($coins as $coin)
		{
			$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

			$b = $remote->validateaddress($user->username);
			if(!$b || !isset($b['isvalid']) || !$b['isvalid']) continue;

			$user->balance = 0;
			$user->coinid = $coin->id;

			debuglog("$user->username set to $coin->symbol, balance $user->balance");
			break;
		}

		if (empty($user->coinid)) {
			debuglog("$user->username is an unknown address!");
		}

		$user->save();
	}

//	$delay=time()-60*60;
//	$list = dborun("update coins set dontsell=1 where id in (select coinid from accounts where balance>0 or last_login>$delay group by coinid)");
//	$list = dborun("update coins set dontsell=0 where id not in (select coinid from accounts where balance>0 or last_login>$delay group by coinid)");


//	$list = getdbolist('db_workers', "dns is null");
//	foreach($list as $worker)
//	{
//		$worker->dns = $worker->ip;
//		$res = system("resolveip $worker->ip");

//		if($res)
//		{
//			$a = explode(' ', $res);
//			if($a && isset($a[5]))
//				$worker->dns = $a[5];
//		}

//		$worker->save();
//	}

	$d1 = microtime(true) - $t1;
	controller()->memcache->add_monitoring_function(__METHOD__, $d1);
}


