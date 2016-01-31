<?php

function updateRawcoins()
{
//	debuglog(__FUNCTION__);

	$list = bittrex_api_query('public/getcurrencies');
	if(isset($list->result))
	{
		dborun("update markets set deleted=true where name='bittrex'");
		foreach($list->result as $currency)
			updateRawCoin('bittrex', $currency->Currency, $currency->CurrencyLong);
	}

	$list = bleutrade_api_query('public/getcurrencies');
	if(isset($list->result))
	{
		dborun("update markets set deleted=true where name='bleutrade'");
		foreach($list->result as $currency)
			updateRawCoin('bleutrade', $currency->Currency, $currency->CurrencyLong);
	}

	$poloniex = new poloniex;
	$tickers = $poloniex->get_currencies();
	if (!$tickers)
		$tickers = array();
	else
		dborun("update markets set deleted=true where name='poloniex'");
	foreach($tickers as $symbol=>$ticker)
	{
		if($ticker['disabled']) continue;
		if($ticker['delisted']) continue;
		updateRawCoin('poloniex', $symbol);
	}

	$ccex = new CcexAPI;
	$list = $ccex->getPairs();
	if($list)
	{
		dborun("update markets set deleted=true where name='c-cex'");
		foreach($list as $item)
		{
			$e = explode('-', $item);
			$symbol = strtoupper($e[0]);

			updateRawCoin('c-cex', $symbol);
		}
	}

	$list = bter_api_query('marketlist');
	if(is_object($list) && is_array($list->data))
	{
		dborun("UPDATE markets SET deleted=true WHERE name='bter'");
		foreach($list->data as $item) {
			if (strtoupper($item->curr_b) !== 'BTC')
				continue;
			if (strpos($item->name, 'Asset') !== false)
				continue;
			if (strpos($item->name, 'BitShares') !== false && $item->symbol != 'BTS')
				continue;
			// ignore some dead coins and assets
			if (in_array($item->symbol, array('BITGLD','DICE','ROX','TOKEN')))
				continue;
			updateRawCoin('bter', $item->symbol, $item->name);
		}
	}

	$list = cryptsy_api_query('getmarkets');
	if(isset($list['return']))
	{
		dborun("update markets set deleted=true where name='cryptsy'");
// disabled
//		foreach($list['return'] as $item)
//			updateRawCoin('cryptsy', $item['primary_currency_code'], $item['primary_currency_name']);
	}

	$res = yobit_api_query('info');
	if($res)
	{
		dborun("UPDATE markets SET deleted=true WHERE name='yobit'");
		foreach($res->pairs as $i=>$item)
		{
			$e = explode('_', $i);
			$symbol = strtoupper($e[0]);
			updateRawCoin('yobit', $symbol);
		}
	}

	$list = safecex_api_query('getmarkets');
	if(!empty($list))
	{
		dborun("UPDATE markets SET deleted=true WHERE name='safecex'");
		foreach($list as $pair => $item) {
			$e = explode('/', $item->market);
			if (strtoupper($e[1]) !== 'BTC')
				continue;
			$symbol = strtoupper($e[0]);
			updateRawCoin('safecex', $symbol, $item->name);
		}
	}

	$list = cryptopia_api_query('GetMarkets');
	if(isset($list->Data))
	{
		dborun("UPDATE markets SET deleted=true WHERE name='cryptopia'");
		foreach($list->Data as $item) {
			$e = explode('/', $item->Label);
			if (strtoupper($e[1]) !== 'BTC')
				continue;
			$symbol = strtoupper($e[0]);
			updateRawCoin('cryptopia', $symbol);
		}
	}

	$list = alcurex_api_query('market','?info=on');
	if(is_object($list) && isset($list->MARKETS))
	{
		dborun("UPDATE markets SET deleted=true WHERE name='alcurex'");
		foreach($list->MARKETS as $item) {
			$e = explode('_', $item->Pair);
			$symbol = strtoupper($e[0]);
			updateRawCoin('alcurex', $symbol);
		}
	}

	$list = banx_simple_api_query('marketsv2');
	if(is_array($list))
	{
		dborun("UPDATE markets SET deleted=true WHERE name='banx'");
		foreach($list as $item) {
			$e = explode('/', $item->market);
			$base = strtoupper($e[1]);
			if ($base != 'BTC')
				continue;
			$symbol = strtoupper($e[0]);
			if ($symbol == 'ATP')
				continue;
			$name = explode('/',$item->marketname);
			updateRawCoin('banx', $symbol, $name[0]);
			//debuglog("banx: $symbol {$name[0]}");
		}
	}

	$list = empoex_api_query('marketinfo');
	if(is_array($list))
	{
		dborun("UPDATE markets SET deleted=true WHERE name='empoex'");
		foreach($list as $item) {
			$e = explode('-', $item->pairname);
			$base = strtoupper($e[1]);
			if ($base != 'BTC')
				continue;
			$symbol = strtoupper($e[0]);
			updateRawCoin('empoex', $symbol);
		}
	}

	//////////////////////////////////////////////////////////

	dborun("delete from markets where deleted");

	$list = getdbolist('db_coins', "not enable and not installed and id not in (select distinct coinid from markets)");
	foreach($list as $coin)
	{
		debuglog("$coin->symbol is not longer active");
	//	if ($coin->symbol != 'BTC')
	//		$coin->delete();
	}
}

function updateRawCoin($marketname, $symbol, $name='unknown')
{
	if($symbol == 'BTC') return;

	$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
	if(!$coin && $marketname != 'yobit')
	{
		$algo = '';
		if ($marketname == 'cryptopia') {
			// get coin label and algo (different api)
			$labels = cryptopia_api_query('GetCurrencies');
			if (is_object($labels) && !empty($labels->Data)) {
				foreach ($labels->Data as $coin) {
					if ($coin->Symbol == $symbol) {
						$name = $coin->Name;
						$algo = strtolower($coin->Algorithm);
						break;
					}
				}
			}
		}

		debuglog("new coin $marketname $symbol $name");

		$coin = new db_coins;
		$coin->txmessage = true;
		$coin->hassubmitblock = true;
		$coin->name = $name;
		$coin->algo = $algo;
		$coin->symbol = $symbol;
		$coin->created = time();
		$coin->save();

		mail(YAAMP_ADMIN_EMAIL, "New coin $symbol", "new coin $symbol ($name) on $marketname");
		sleep(30);
	}

	else if($coin && $coin->name == 'unknown' && $name != 'unknown')
	{
		$coin->name = $name;
		$coin->save();
	}

	$list = getdbolist('db_coins', "symbol=:symbol or symbol2=:symbol", array(':symbol'=>$symbol));
	foreach($list as $coin)
	{
		$market = getdbosql('db_markets', "coinid=$coin->id and name='$marketname'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = $marketname;
		}

		$market->deleted = false;
		$market->save();
	}

	/////////

//	if($coin->enable || !empty($coin->algo) || !empty($coin->errors) || $coin->name == 'unknown') return;
//	debuglog("http://www.cryptocoinrank.com/$coin->name");

//	$data = file_get_contents("http://www.cryptocoinrank.com/$coin->name");
//	if($data)
//	{
//		$b = preg_match('/Algo: <span class=\"d-gray\">(.*)<\/span>/', $data, $m);
//		if($b)
//		{
//			$coin->errors = trim($m[1]);
//			$coin->save();
//		}
//	}

}

