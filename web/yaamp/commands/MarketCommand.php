<?php
/**
 * MarketCommand is a console command, to query market and history
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * php web/yaamp/yiic.php market help
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class MarketCommand extends CConsoleCommand
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

		$symbol = arraySafeVal($args, 0);

		if (!isset($args[1])) {

			echo "Yiimp market command\n";
			echo "Usage: yiimp market <SYM> list\n";
			echo "       yiimp market <SYM> histo <market>\n";
			echo "       yiimp market <SYM> prune\n";
			return 1;

		} else if ($args[1] == 'list') {

			$this->listMarkets($symbol);

			return 0;

		} else if ($args[1] == 'histo') {

			$market = arraySafeVal($args,2,'');
			if (empty($market)) die("Usage: yiimp market <SYM> histo <market>\n");

			$this->queryMarketHistory($symbol, $market);

			return 0;

		} else if ($args[1] == 'prune') {

			marketHistoryPrune($symbol);

			return 0;
		}
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return parent::getHelp().'market';
	}

	/**
	 * List markets of a currency
	 */
	public function listMarkets($symbol)
	{
		require_once($this->basePath.'/yaamp/core/core.php');

		$coin = getdbosql('db_coins', "symbol=:sym", array(':sym'=>$symbol));
		if (!$coin) die("coin $symbol not found!\n");

		$markets = new db_markets;
		foreach ($markets->findAll("coinid={$coin->id} ORDER BY disabled, price DESC") as $market) {
			$price = $market->disabled ? '*DISABLED*' : bitcoinvaluetoa($market->price);
			echo "{$price} {$market->name}\n";
		}
	}

	/**
	 * Query market history of a "watched" currency
	 */
	public function queryMarketHistory($symbol, $market)
	{
		require_once($this->basePath.'/yaamp/core/core.php');

		$coin = getdbosql('db_coins', "symbol=:sym", array(':sym'=>$symbol));
		if (!$coin) die("coin $symbol not found!\n");

		//$last_update = (int) dboscalar(
		//	"SELECT time FROM market_history WHERE idcoin={$coin->id} ORDER BY id DESC LIMIT 1"
		//);
		//if (time() - $last_update > 300) BackendWatchMarkets();

		$history = new db_market_history;
		$c = new CDbCriteria;
		$c->condition = "idcoin={$coin->id}";
		if ($coin->symbol != 'BTC') $c->condition .= " AND mh_market.name='$market'"; // table alias set in model
		$c->order = 'time DESC';
		$c->limit = 100;
		$items = getdbolistWith('db_market_history', 'market', $c);

		foreach ($items as $histo) {
			$date = strftime('%F %T', $histo->time);
			$price1 = bitcoinvaluetoa($histo->price);
			$price2 = bitcoinvaluetoa($histo->price2);
			echo "$date $price1 $price2\n";
		}
	}
}
