<?php

require_once('serverconfig.php');

function ld($string)
{
	$d = date('h:i:s');
	echo("$d - $string\n");
}

class CronjobController extends CommonController
{
	private function monitorApache()
	{
		if(!YAAMP_PRODUCTION) return;
		if(!YAAMP_USE_NGINX) return;

		$uptime = exec('uptime');

		$apache_locked = memcache_get($this->memcache->memcache, 'apache_locked');
		if($apache_locked) return;

		$b = preg_match('/load average: (.*)$/', $uptime, $m);
		if(!$b) return;

		$e = explode(', ', $m[1]);

		$webserver = 'nginx';
		$res = exec("pgrep $webserver");
		$webserver_running = !empty($res);

		if($e[0] > 4 && $webserver_running)
		{
			debuglog('server overload!');
	//		debuglog('stopping webserver');
	//		system("service $webserver stop");
			sleep(1);
		}

		else if(!$webserver_running)
		{
			debuglog('starting webserver');
			system("service $webserver start");
		}
	}

	public function actionRunBlocks()
	{
//		debuglog(__METHOD__);
		set_time_limit(0);

		$this->monitorApache();

		$last_complete = memcache_get($this->memcache->memcache, "cronjob_block_time_start");
		if($last_complete+5*60<time())
			dborun("update jobs set active=false");

		BackendBlockFind1();
		BackendClearEarnings();
		BackendRentingUpdate();
		BackendProcessList();

		memcache_set($this->memcache->memcache, "cronjob_block_time_start", time());
//		debuglog(__METHOD__);
	}

	public function actionRunLoop2()
	{
//		debuglog(__METHOD__);
		set_time_limit(0);

		$this->monitorApache();

		BackendCoinsUpdate();
		BackendStatsUpdate();
		BackendUsersUpdate();

		BackendUpdateServices();
		BackendUpdateDeposit();

		MonitorBTC();

		$last = memcache_get($this->memcache->memcache, 'last_renting_payout2');
		if($last + 5*60 < time())
		{
			memcache_set($this->memcache->memcache, 'last_renting_payout2', time());
			BackendRentingPayout();
		}

		$last = memcache_get($this->memcache->memcache, 'last_stats2');
		if($last + 5*60 < time())
		{
			memcache_set($this->memcache->memcache, 'last_stats2', time());
			BackendStatsUpdate2();
		}

		memcache_set($this->memcache->memcache, "cronjob_loop2_time_start", time());
//		debuglog(__METHOD__);
	}

	public function actionRun()
	{
//		debuglog(__METHOD__);
		set_time_limit(0);

//		BackendRunCoinActions();

		$state = memcache_get($this->memcache->memcache, 'cronjob_main_state');
		if(!$state) $state = 0;

		memcache_set($this->memcache->memcache, 'cronjob_main_state', $state+1);
		memcache_set($this->memcache->memcache, "cronjob_main_state_$state", 1);

		switch($state)
		{
			case 0:
				updateRawcoins();

				$a = json_decode(fetch_url("https://www.bitstamp.net/api/ticker/"));
				if($a && isset($a->last))
				{
					$mining = getdbosql('db_mining');
					$mining->usdbtc = $a->last;
					$mining->save();
				}

				break;

			case 1:
				if(!YAAMP_PRODUCTION) break;

				doBittrexTrading();
				doCryptsyTrading();
				doBleutradeTrading();
				doPoloniexTrading();
				doYobitTrading();
				doCCexTrading();
				//doAlcurexTrading();
				//doCryptopiaTrading();
				doEmpoexTrading();

				break;

			case 2:
				BackendPricesUpdate();
				break;

			case 3:
				BackendBlocksUpdate();
				break;

			case 4:
				TradingSellCoins();
				break;

			case 5:
				BackendBlockFind2();
				break;

			default:
				memcache_set($this->memcache->memcache, 'cronjob_main_state', 0);
				BackendQuickClean();

			//	sleep(120);

				$t = memcache_get($this->memcache->memcache, "cronjob_main_start_time");
				$n = time();

				memcache_set($this->memcache->memcache, "cronjob_main_time", $n-$t);
				memcache_set($this->memcache->memcache, "cronjob_main_start_time", $n);
		}

		debuglog(__METHOD__." $state");
		memcache_set($this->memcache->memcache, "cronjob_main_state_$state", 0);

		memcache_set($this->memcache->memcache, "cronjob_main_time_start", time());
		if(!YAAMP_PRODUCTION) return;

		///////////////////////////////////////////////////////////////////

		$mining = getdbosql('db_mining');
		if($mining->last_payout + YAAMP_PAYMENTS_FREQ > time()) return;

		debuglog("--------------------------------------------------------");

		$mining->last_payout = time();
		$mining->save();

		memcache_set($this->memcache->memcache, 'apache_locked', true);
		if(YAAMP_USE_NGINX)
			system("service nginx stop");

		sleep(10);
		BackendDoBackup();

		memcache_set($this->memcache->memcache, 'apache_locked', false);

		BackendPayments();
		BackendCleanDatabase();

	//	BackendOptimizeTables();
		debuglog('payments sequence done');
	}

}

