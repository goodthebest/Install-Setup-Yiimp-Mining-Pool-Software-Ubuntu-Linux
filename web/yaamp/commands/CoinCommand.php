<?php
/**
 * CoinCommand is a console command, to get/set coin user settings
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * php web/yaamp/yiic.php coin help
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class CoinCommand extends CConsoleCommand
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

		if (!isset($args[1]) || $args[1] == 'help') {

			echo "Yiimp coin command\n";
			echo "Usage: yiimp coin <SYM> get <key>\n";
			echo "       yiimp coin <SYM> set <key> <value>\n";
			echo "       yiimp coin <SYM> unset <key>\n";
			echo "       yiimp coin <SYM> settings\n";
			return 1;

		} else if ($args[1] == 'get') {
			return $this->getCoinSetting($args);

		} else if ($args[1] == 'set') {
			return $this->setCoinSetting($args);

		} else if ($args[1] == 'unset') {
			return $this->unsetCoinSetting($args);

		} else if ($args[1] == 'settings') {
			return $this->listCoinSettings($args);
		}
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return $this->run(array('help'));
	}

	private function checkSymbol($symbol)
	{
		return dboscalar("SELECT COUNT(*) FROM coins WHERE symbol=:symbol",
			array(':symbol'=>$symbol));
	}

	public function getCoinSetting($args)
	{
		if (count($args) < 3)
			die("usage: yiimp coin <SYM> get <key>\n");
		$symbol   = $args[0];
		$key      = $args[2];
		if (!$this->checkSymbol($symbol)) {
			echo "error: symbol '$symbol' does not exist!\n";
			return 1;
		}
		$value = coin_get($symbol, $key);
		echo "$value\n";
		return 0;
	}

	public function setCoinSetting($args)
	{
		if (count($args) < 4)
			die("usage: yiimp coin <SYM> set <key> <value>\n");
		$symbol   = $args[0];
		$key      = $args[2];
		$value    = $args[3];
		if (!$this->checkSymbol($symbol)) {
			echo "error: symbol '$symbol' does not exist!\n";
			return 1;
		}
		$res = coin_set($symbol, $key, $value);
		$val = coin_get($symbol, $key);
		echo ($res ? "$symbol $exchange $key ".json_encode($val) : "error") . "\n";
		return 0;
	}

	public function unsetCoinSetting($args)
	{
		if (count($args) < 3)
			die("usage: yiimp coin <SYM> unset <key>\n");
		$symbol   = $args[0];
		$key      = $args[2];
		if (!$this->checkSymbol($symbol)) {
			echo "error: symbol '$symbol' does not exist!\n";
			return 1;
		}
		coin_unset($symbol, $key);
		echo "ok\n";
		return 0;
	}

	public function listCoinSettings($args)
	{
		if (count($args) < 2)
			die("usage: yiimp coin <SYM> settings\n");
		$symbol = $args[0];
		if (!$this->checkSymbol($symbol)) {
			echo "error: symbol '$symbol' does not exist!\n";
			return 1;
		}
		$keys = coin_settings_prefetch($symbol);
		foreach ($keys as $key => $value) {
			if ($key == $symbol) continue;
			echo "$key ".json_encode($value)."\n";
		}
		return 0;
	}

}
