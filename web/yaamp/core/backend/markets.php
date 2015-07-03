<?php

function BackendPricesUpdate()
{
//	debuglog(__FUNCTION__);

	updateBittrexMarkets();
	updateCryptsyMarkets();
	updateCCexMarkets();
	updateBleutradeMarkets();
	updatePoloniexMarkets();
	updateYobitMarkets();
	updateJubiMarkets();
	updateAlcurexMarkets();
	updateCryptopiaMarkets();

	$list2 = getdbolist('db_coins', "installed and symbol2 is not null");
	foreach($list2 as $coin2)
	{
		$coin = getdbosql('db_coins', "symbol='$coin2->symbol2'");
		if(!$coin) continue;

		$list = getdbolist('db_markets', "coinid=$coin->id");
		foreach($list as $market)
		{
			$market2 = getdbosql('db_markets', "coinid=$coin2->id and name='$market->name'");
			if(!$market2) continue;

			$market2->price = $market->price;
			$market2->price2 = $market->price2;
			$market2->deposit_address = $market->deposit_address;

			$market2->save();
		}
	}

	$coins = getdbolist('db_coins', "installed and id in (select distinct coinid from markets)");
	foreach($coins as $coin)
	{
		if($coin->symbol=='BTC')
		{
			$coin->price = 1;
			$coin->price2 = 1;

			$coin->save();
			continue;
		}

		$market = getBestMarket($coin);
		if($market)
		{
			$coin->price = $market->price*(1-YAAMP_FEES_EXCHANGE/100);
			$coin->price2 = $market->price2;

			$base_coin = !empty($market->base_coin)? getdbosql('db_coins', "symbol='$market->base_coin'"): null;
			if($base_coin)
			{
				$coin->price *= $base_coin->price;
				$coin->price2 *= $base_coin->price;
			}

//			if($market->name == 'c-cex')
//				$coin->price *= 0.95;
		}

		else
		{
			$coin->price = 0;
			$coin->price2 = 0;
		}

		$coin->save();
		dborun("update earnings set price=$coin->price where status!=2 and coinid=$coin->id");
	}
}

function getBestMarket($coin)
{
	$market = getdbosql('db_markets', "coinid=$coin->id and price!=0 and
		deposit_address is not null and deposit_address != '' and
		(name='bittrex' or name='cryptsy') order by price desc");

	if(!$market)
	{
		$market = getdbosql('db_markets', "coinid=$coin->id and price!=0 and
			deposit_address is not null and deposit_address != '' and
			name!='yobit' order by price desc");

		if(!$market)
			$market = getdbosql('db_markets', "coinid=$coin->id and price!=0 and
					deposit_address is not null and deposit_address != ''
					order by price desc");
	}

	return $market;
}

function AverageIncrement($value1, $value2)
{
	$percent = 80;
	$value = ($value1*(100-$percent) + $value2*$percent) / 100;

	return $value;
}

///////////////////////////////////////////////////////////////////////////////////////////////////

function updateBleutradeMarkets()
{
	$list = bleutrade_api_query('public/getcurrencies');
	if(!is_object($list)) return;

	foreach($list->result as $currency)
	{
		//	debuglog($currency);
		if($currency->Currency == 'BTC') continue;

		$coin = getdbosql('db_coins', "symbol='$currency->Currency'");
		if(!$coin || !$coin->installed) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='bleutrade'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = 'bleutrade';
		}

		$market->txfee = $currency->TxFee;
		if(!$currency->IsActive)
		{
			$market->price = 0;
			$market->save();

			continue;
		}

		$market->save();
		$pair = "{$coin->symbol}_BTC";

		$ticker = bleutrade_api_query('public/getticker', "&market=$pair");
		if(!$ticker || !$ticker->success || !$ticker->result) continue;

		if(empty($market->deposit_address))
		{
			$address = bleutrade_api_query('account/getdepositaddress', "&currency=$coin->symbol");

			if(is_object($address) && is_object($address->result))
				$market->deposit_address = $address->result->Address;
		}

		$price2 = ($ticker->result[0]->Bid+$ticker->result[0]->Ask)/2;
		$market->price2 = AverageIncrement($market->price2, $price2);
		$market->price = AverageIncrement($market->price, $ticker->result[0]->Bid);

		$market->save();
	}

}

/////////////////////////////////////////////////////////////////////////////////////////////

function updateBittrexMarkets()
{
	$list = bittrex_api_query('public/getcurrencies');
	if(!is_object($list)) return;

	foreach($list->result as $currency)
	{
		if($currency->Currency == 'BTC') continue;

		$coin = getdbosql('db_coins', "symbol='$currency->Currency'");
		if(!$coin || !$coin->installed) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='bittrex'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = 'bittrex';
		}

		$market->txfee = $currency->TxFee;
		$market->message = $currency->Notice;

		if($coin->symbol == 'EGMA')
		{
			$market->price = 0.00000001;
			$market->save();

			continue;
		}

		if(!$currency->IsActive)
		{
			$market->price = 0;
			$market->save();

			continue;
		}

		$market->save();
		$pair = "BTC-$coin->symbol";

		$ticker = bittrex_api_query('public/getticker', "&market=$pair");
		if(!$ticker || !$ticker->success || !$ticker->result) continue;

		if(empty($market->deposit_address))
		{
			$address = bittrex_api_query('account/getdepositaddress', "&currency=$coin->symbol");

			if(is_object($address) && isset($address->result))
				$market->deposit_address = $address->result->Address;
		}

		$price2 = ($ticker->result->Bid+$ticker->result->Ask)/2;
		$market->price2 = AverageIncrement($market->price2, $price2);
		$market->price = AverageIncrement($market->price, $ticker->result->Bid);

		$market->save();
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////

function updateCryptsyMarkets()
{
	//	dborun("update markets set price=0 where name='cryptsy'");
	//	return;

	$markets = cryptsy_api_query('getmarkets');
	if(!$markets || !isset($markets['return']))
	{
		debuglog($markets);
		return;
	}

	$list = cryptsy_api_query('getcoindata');
	if(!$list || !isset($list['return']))
	{
		debuglog($list);
		return;
	}

	foreach($list['return'] as $currency)
	{
		if($currency['code'] == 'BTC') continue;
		$symbol = $currency['code'];

		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
		if(!$coin || !$coin->installed) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='cryptsy'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = 'cryptsy';

			foreach($markets['return'] as $item)
			{
				if($item['secondary_currency_code'] != 'BTC') continue;
				if($item['primary_currency_code'] != $symbol) continue;

				$market->marketid = $item['marketid'];
			}
		}

		if(empty($market->marketid))
		{
			foreach($markets['return'] as $item)
			{
				if($item['secondary_currency_code'] != 'BTC') continue;
				if($item['primary_currency_code'] != $symbol) continue;

				$market->marketid = $item['marketid'];
			}
		}

		$market->txfee = $currency['withdrawalfee']*100;
		switch($currency['maintenancemode'])
		{
			case 0:
				$market->message = '';
				break;
			case 1:
				$market->message = 'Maintenance';
				break;
			case 2:
				$market->message = 'Updating Wallet';
				break;
			case 3:
				$market->message = 'Network Issues';
				break;
			default:
				$market->message = 'Unknown Error';
				break;
		}

		$market->save();

		if($currency['maintenancemode'])
		{
			$market->price = 0;
			$market->save();

			continue;
		}

		$ticker = getCryptsyTicker($market->marketid);
	//	debuglog($ticker);
		if(!$ticker) continue;
		if(!isset($ticker->return->$symbol->buyorders[0]))
		{
			debuglog("error cryptsy $coin->name");
			debuglog($ticker, 5);
			continue;
		}

		$price2 = ($ticker->return->$symbol->buyorders[0]->price+$ticker->return->$symbol->sellorders[0]->price)/2;
		$market->price2 = AverageIncrement($market->price2, $price2);
		$market->price = AverageIncrement($market->price, $ticker->return->$symbol->buyorders[0]->price);

		$market->save();
	}

	$list = cryptsy_api_query('getmydepositaddresses');
	if (empty($list)) return;

	foreach($list['return'] as $symbol=>$item)
	{
		//		debuglog($item);
		if($symbol == 'BTC') continue;

		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
		if(!$coin) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='cryptsy'");
		if(!$market) continue;

		$market->deposit_address = $item;
		$market->save();
	}

}

////////////////////////////////////////////////////////////////////////////////////

function updateCCexMarkets()
{
	//	dborun("update markets set price=0 where name='c-cex'");	<- add that line
	$ccex = new CcexAPI;

	$list = $ccex->getPairs();
	if (!is_array($list)) return;

	foreach($list as $item)
	{
		$e = explode('-', $item);
		if(!isset($e[1])) continue;
		if($e[1] != 'btc') continue;

		$symbol = strtoupper($e[0]);

		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
		if(!$coin || !$coin->installed) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='c-cex'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = 'c-cex';
		}

		$market->save();

		$ticker = $ccex->getTickerInfo($item);
		if(!$ticker) continue;

		$price2 = ($ticker['buy']+$ticker['sell'])/2;
		$market->price2 = AverageIncrement($market->price2, $price2);
		$market->price = AverageIncrement($market->price, $ticker['buy']);

		$market->save();
	}
}

////////////////////////////////////////////////////////////////////////////////////

function updatePoloniexMarkets()
{
	$poloniex = new poloniex;

	$tickers = $poloniex->get_ticker();
	if(!is_array($tickers)) return;

	foreach($tickers as $symbol=>$ticker)
	{
		$a = explode('_', $symbol);
		if(!isset($a[1])) continue;
		if($a[0] != 'BTC') continue;

		$symbol = $a[1];

		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
		if(!$coin || !$coin->installed) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='poloniex'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = 'poloniex';
		}

		if(empty($market->deposit_address) && $coin->installed)
			$poloniex->generate_address($coin->symbol);

		$price2 = ($ticker['highestBid']+$ticker['lowestAsk'])/2;
		$market->price2 = AverageIncrement($market->price2, $price2);
		$market->price = AverageIncrement($market->price, $ticker['highestBid']);

		$market->save();
	}

	$list = $poloniex->get_deposit_addresses();
	if (!is_array($list)) return;

	foreach($list as $symbol=>$item)
	{
		if($symbol == 'BTC') continue;

		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
		if(!$coin) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='poloniex'");
		if(!$market) continue;

		$market->deposit_address = $item;
		$market->save();
	}
}

////////////////////////////////////////////////////////////////////////////////////

function updateYobitMarkets()
{
	$res = yobit_api_query('info');
	if(!is_object($res)) return;

	foreach($res->pairs as $i=>$item)
	{
		$e = explode('_', $i);
		$symbol = strtoupper($e[0]);
		if($e[1] != 'btc') continue;
		if($symbol == 'BTC') continue;

		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
		if(!$coin || !$coin->installed) continue;

		$market = getdbosql('db_markets', "coinid=$coin->id and name='yobit'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = 'yobit';
		}

		$pair = strtolower($coin->symbol).'_btc';

		$ticker = yobit_api_query("ticker/$pair");
		if(!$ticker) continue;

		$price2 = ($ticker->$pair->buy+$ticker->$pair->sell)/2;
		$market->price2 = AverageIncrement($market->price2, $price2);
		$market->price = AverageIncrement($market->price, $ticker->$pair->buy);

		$market->save();
	}
}

function updateJubiMarkets()
{
	$btc = jubi_api_query('ticker', "?coin=btc");
	if(!is_object($btc)) return;

	$list = getdbolist('db_markets', "name='jubi'");
	foreach($list as $market)
	{
		$coin = getdbo('db_coins', $market->coinid);
		if(!$coin) continue;

		$lowsymbol = strtolower($coin->symbol);

		$ticker = jubi_api_query('ticker', "?coin=$lowsymbol");
		if(!is_object($ticker)) continue;

		if (isset($btc->sell) && $btc->sell != 0.)
			$ticker->buy /= $btc->sell;
		if (isset($btc->buy) && $btc->buy != 0.)
			$ticker->sell /= $btc->buy;

		$price2 = ($ticker->buy+$ticker->sell)/2;
		$market->price2 = AverageIncrement($market->price2, $price2);
		$market->price = AverageIncrement($market->price, $ticker->buy*0.95);

		$market->save();
	}
}

function updateAlcurexMarkets()
{
	$data = alcurex_api_query('market', "?info=on");
	if(!is_object($data)) return;

	$list = getdbolist('db_markets', "name='alcurex'");
	foreach($list as $market)
	{
		$coin = getdbo('db_coins', $market->coinid);
		if(!$coin || !$coin->installed) continue;
		$pair = strtoupper($coin->symbol).'_BTC';
		foreach ($data->MARKETS as $ticker) {
			if ($ticker->Pair === $pair) {
				$lpair = strtolower($pair);
				$last = alcurex_api_query('market', "?pair=$lpair&last=last");
				if (is_object($last) && !empty($last->$lpair)) {
					$last = reset($last->$lpair);
					$market->price = AverageIncrement($market->price, $last->price);
					$market->save();
				}
				$last = alcurex_api_query('market', "?pair=$lpair&last=sell");
				if (is_object($last) && !empty($last->$lpair)) {
					$last = reset($last->$lpair);
					$market->price2 = AverageIncrement($market->price2, $last->price);
					$market->save();
				}
				debuglog("alcurex... should Update $pair: $market->price $market->price2");
			}
		}
	}
}

function updateCryptopiaMarkets()
{
	$data = cryptopia_api_query('GetMarkets', 24);
	if(!is_object($data)) return;

	$list = getdbolist('db_markets', "name='cryptopia'");
	foreach($list as $market)
	{
		$coin = getdbo('db_coins', $market->coinid);
		if(!$coin || !$coin->installed) continue;

		$pair = strtoupper($coin->symbol).'/BTC';

		foreach ($data->Data as $ticker) {
			if ($ticker->Label === $pair) {

				$price2 = ($ticker->BidPrice+$ticker->AskPrice)/2;
				$market->price2 = AverageIncrement($market->price2, $price2);
				$market->price = AverageIncrement($market->price, $ticker->BidPrice*0.98);
				// debuglog("Updated $pair: $market->price");
				$market->save();
				break;
			}
		}
	}
}

