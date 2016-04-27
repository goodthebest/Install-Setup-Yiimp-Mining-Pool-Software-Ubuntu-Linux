<?php
/**
 * ExchangeCommand is a console command, to check private apis keys
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * php web/yaamp/yiic.php exchange test
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class ExchangeCommand extends CConsoleCommand
{
	protected $basePath;

	/**
	 * Execute the action.
	 * @param array $args command line parameters specific for this command
	 * @return integer non zero application exit code after printing help
	 */
	public function run($args)
	{
		$runner=$this->getCommandRunner();
		$commands=$runner->commands;

		$root = realpath(Yii::app()->getBasePath().DIRECTORY_SEPARATOR.'..');
		$this->basePath = str_replace(DIRECTORY_SEPARATOR, '/', $root);

		if (!isset($args[0])) {

			echo "Yiimp exchange command\n";
			echo "Usage: yiimp exchange apitest\n";
			echo "       yiimp exchange get <exchange> <key>\n";
			echo "       yiimp exchange set <exchange> <key> <value>\n";
			echo "       yiimp exchange unset <exchange> <key>\n";
			echo "       yiimp exchange settings <exchange>\n";
			return 1;

		} else if ($args[0] == 'get') {
			return $this->getExchangeSetting($args);

		} else if ($args[0] == 'set') {
			return $this->setExchangeSetting($args);

		} else if ($args[0] == 'unset') {
			return $this->unsetExchangeSetting($args);

		} else if ($args[0] == 'settings') {
			return $this->listExchangeSettings($args);

		} else if ($args[0] == 'apitest') {
			$this->testApiKeys();
			return 0;
		}
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return parent::getHelp().'exchange';
	}

	public function getExchangeSetting($args)
	{
		if (count($args) < 3)
			die("usage: yiimp exchange get <exchange> <key>\n");
		$exchange = $args[1];
		$key = $args[2];
		$value = exchange_get($exchange, $key);
		echo "$value\n";
		return 0;
	}

	public function setExchangeSetting($args)
	{
		if (count($args) < 4)
			die("usage: yiimp exchange set <exchange> <key> <value>\n");
		$exchange = $args[1];
		$key      = $args[2];
		$value    = $args[3];
		$keys = exchange_valid_keys($exchange);
		if (!isset($keys[$key])) {
			echo "error: key '$key' is not handled!\n";
			return 1;
		}
		$res = exchange_set($exchange, $key, $value);
		$val = exchange_get($exchange, $key);
		echo ($res ? "$exchange $key ".json_encode($val) : "error") . "\n";
		return 0;
	}

	public function unsetExchangeSetting($args)
	{
		if (count($args) < 3)
			die("usage: yiimp exchange unset <exchange> <key>\n");
		$exchange = $args[1];
		$key      = $args[2];
		exchange_unset($exchange, $key);
		echo "ok\n";
		return 0;
	}

	public function listExchangeSettings($args)
	{
		if (count($args) < 2)
			die("usage: yiimp exchange settings <exchange>\n");
		$exchange = $args[1];
		$keys = exchange_valid_keys($exchange);
		foreach ($keys as $key => $desc) {
			$val = exchange_get($exchange, $key);
			if ($val !== null) {
				//echo "$desc\n";
				echo "$exchange $key ".json_encode($val)."\n";
			}
		}
		return 0;
	}

	public function testApiKeys()
	{
		require_once($this->basePath.'/yaamp/core/core.php');

		if (!empty(EXCH_BITTREX_KEY)) {
			$balance = bittrex_api_query('account/getbalance','&currency=BTC');
			if (!is_object($balance)) echo "bittrex error\n";
			else echo("bittrex btc: ".json_encode($balance->result)."\n");
		}
		if (!empty(EXCH_BLEUTRADE_KEY)) {
			$balance = bleutrade_api_query('account/getbalances','&currencies=BTC');
			//$balance = bleutrade_api_query('account/getbalances');
			if (!is_object($balance)) echo "bleutrade error\n";
			else echo("bleutrade btc: ".json_encode($balance->result)."\n");
		}
		if (!empty(EXCH_BTER_KEY)) {
			$info = bter_api_user('getfunds');
			if (!$info || arraySafeVal($info,'result') != 'true' || !isset($info['available_funds'])) echo "bter error\n";
			else echo("bter available: ".json_encode($info['available_funds'])."\n");
		}
		if (!empty(EXCH_CCEX_KEY)) {
			$ccex = new CcexAPI;
			$balances = $ccex->getBalance();
			if(!$balances || !isset($balances['return'])) echo "error\n";
			else echo("c-cex btc: ".json_encode($balances['return'][1])."\n");
		}
		if (!empty(EXCH_CRYPTOPIA_KEY)) {
			$balance = cryptopia_api_user('GetBalance',array("Currency"=>"BTC"));
			echo("cryptopia btc: ".json_encode($balance->Data)."\n");
		}
		if (!empty(EXCH_KRAKEN_KEY)) {
			$balance = kraken_api_user('Balance');
			echo("kraken btc: ".json_encode($balance)."\n");
		}
		if (!empty(EXCH_NOVA_KEY)) {
			$info = nova_api_user('getbalances');
			if (objSafeVal($info,'status','') != 'success' || !is_array($info->balances)) echo "nova error\n";
			else echo("nova btc: ".json_encode($info->balances[0])."\n");
		}
		if (!empty(EXCH_CRYPTSY_KEY)) {
			$info = cryptsy_api_query('getinfo');
			if (!arraySafeVal($info,'success',0) || !is_array($info['return'])) echo "error\n";
			else echo("cryptsy btc: ".json_encode($info['return']['balances_available']['BTC'])."\n");
		}
		if(!empty(EXCH_POLONIEX_KEY)) {
			$poloniex = new poloniex;
			$balance = $poloniex->get_available_balances();
			echo("poloniex available : ".json_encode($balance)."\n");
		}
		if (!empty(EXCH_SAFECEX_KEY)) {
			$balance = safecex_api_user('getbalance', "&symbol=BTC");
			echo("safecex btc: ".json_encode($balance)."\n");
		}
		if (!empty(EXCH_YOBIT_KEY)) {
			$info = yobit_api_query2('getInfo');
			if (!arraySafeVal($info,'success',0) || !is_array($info['return'])) echo "error\n";
			else echo("yobit btc: ".json_encode($info['return']['funds']['btc'])."\n");
		}
		if (!empty(EXCH_BANX_USERNAME)) {
			//$balance = cryptomic_api_user('account/getbalance','?currency=BTC');
			$balance = cryptomic_api_user('account/getbalances');
			if (!is_object($balance)) echo "cryptomic error ".json_encode($balance)."\n";
			else echo("cryptomic all: ".json_encode($balance->result)."\n");
		}
		if (!empty(EXCH_BITSTAMP_KEY)) {
			$balance = bitstamp_api_user('balance');
			if (!is_object($balance)) echo "bitstamp error ".json_encode($balance)."\n";
			else echo("bitstamp: ".json_encode($balance->result)."\n");
		}

		// only one secret key
		$balance = empoex_api_user('account/balance','BTC');
		if ($balance) echo("empoex btc: ".json_encode($balance['available'])."\n");
	}
}
