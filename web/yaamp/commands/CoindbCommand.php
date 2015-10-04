<?php
/**
 * CoindbCommand is a console command :
 *  - labels: complete Unknown Coins Labels from CryptoCoinCharts.info
 *  - icons: grab coin icons from web sites
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * yiimp coindb labels
 * yiimp coindb icons
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class CoindbCommand extends CConsoleCommand
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

			echo "Yiimp coindb command\n";
			echo "Usage: yiimp coindb [labels|icons]\n";
			return 1;

		} elseif ($args[0] == 'labels') {

			$nbUpdated  = $this->updateCoinsLabels();
			$nbUpdated += $this->updateCryptopiaLabels();
			$nbUpdated += $this->updateFromJson();

			echo "total updated: $nbUpdated\n";
			return 0;

		} elseif ($args[0] == 'icons') {

			$nbUpdated  = $this->grabBterIcons();

			echo "total updated: $nbUpdated\n";
			return 0;
		}
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return parent::getHelp().'coindb labels';
	}

	/**
	 * cryptocoincharts api
	 */
	public static function getCoinChartsData()
	{
		$json = file_get_contents('http://api.cryptocoincharts.info/listCoins');
		$data = json_decode($json,true);
		$array = array();
		foreach ($data as $coin) {
			$key = strtoupper($coin['id']);
			if (empty($key)) continue;
			$array[$key] = $coin;
		}
		return $array;
	}

	public function updateCoinsLabels()
	{
		$modelsPath = $this->basePath.'/yaamp/models';
		if(!is_dir($modelsPath))
			echo "Directory $modelsPath is not a directory\n";

		require_once($modelsPath.'/db_coinsModel.php');

		$nbUpdated = 0;

		$coins = new db_coins;
		if ($coins instanceof CActiveRecord)
		{
			echo "".$coins->count()." coins in the database\n";

			$dataset = $coins->findAll(array('condition'=>'name = :u', 'params'=>array(':u'=>'unknown')));
			$json = self::getCoinChartsData();
			foreach ($dataset as $coin) {
				if ($coin->name == 'unknown' && isset($json[$coin->symbol])) {
					$data = $json[$coin->symbol];
					if ($data['name'] != $coin->symbol) {
						echo "{$coin->symbol}: {$data['name']}\n";
						$coin->name = $data['name'];
						$nbUpdated += $coin->save();
					}
				}
			}
			if ($nbUpdated)
				echo "$nbUpdated coin labels updated from cryptocoincharts.info\n";
		}
		return $nbUpdated;
	}

	/**
	 * Icon grabber
	 */
	public function grabBterIcons()
	{
		$url = 'http://bter.com/images/coin_icon/64/';
		$nbUpdated = 0;
		$sql = "SELECT DISTINCT coins.id FROM coins INNER JOIN markets M ON M.coinid = coins.id WHERE M.name='bter' AND IFNULL(coins.image,'') = ''";
		$coins = dbolist($sql);
		if (empty($coins))
			return;
		echo "bter: try to download new icons...\n";
		foreach ($coins as $coin) {
			$coin = getdbo('db_coins', $coin["id"]);
			$local = $this->basePath."/images/coin-$coin->symbol.png";
			try {
				$data = @ file_get_contents($url.strtolower($coin->symbol).'.png');
			} catch (Exception $e) {
				continue;
			}
			if (strlen($data) < 2048) continue;
			echo $coin->symbol." icon found\n";
			file_put_contents($local, $data);
			if (filesize($local) > 0) {
				$coin->image = "/images/coin-$coin->symbol.png";
				$nbUpdated += $coin->save();
			}
		}
		if ($nbUpdated)
			echo "$nbUpdated images downloaded from bter\n";
		return $nbUpdated;
	}

	/**
	 * Special for cryptopia coins
	 */
	protected function getCryptopiaCurrencies()
	{
		$array = array();
		require_once($this->basePath.'/yaamp/core/exchange/cryptopia.php');
		$data = cryptopia_api_query('GetCurrencies');

		if (is_object($data) && !empty($data->Data))
			foreach ($data->Data as $coin) {
				$key = strtoupper($coin->Symbol);
				if (empty($key)) continue;
				$array[$key] = $coin;
			}

		return $array;
	}

	public function updateCryptopiaLabels()
	{
		$modelsPath = $this->basePath.'/yaamp/models';
		require_once($modelsPath.'/db_coinsModel.php');

		$coins = new db_coins;
		$nbUpdated = 0;

		$dataset = $coins->findAll(array(
			'condition'=>"name=:u OR algo=''",
			'params'=>array(':u'=>'unknown')
		));

		if (!empty($dataset))
		{
			$json = self::getCryptopiaCurrencies();

			foreach ($dataset as $coin) {
				if ($coin->name == 'unknown' && isset($json[$coin->symbol])) {
					$cc = $json[$coin->symbol];
					if ($cc->Name != $coin->symbol) {
						echo "{$coin->symbol}: {$cc->Name}\n";
						$coin->name = $cc->Name;
						if (empty($coin->algo))
							$coin->algo = strtolower($cc->Algorithm);
						$nbUpdated += $coin->save();
					}
				}
			}
			if ($nbUpdated)
				echo "$nbUpdated coin labels updated from cryptopia\n";
		}
		return $nbUpdated;
	}

	/**
	 * To import from a json file placed in the sql/ folder
	 */
	public function updateFromJson()
	{
		$sqlFolder = $this->basePath.'/../sql/';
		$jsonFile = $sqlFolder.'labels.json';
		//$jsonFile = $sqlFolder.'yobit.txt';
		if (!file_exists($jsonFile))
			return 0;

		$nbUpdated = 0;

		$json = json_decode(file_get_contents($jsonFile), true);
	/*
		$json = array();
		$txt = explode("\n", file_get_contents($jsonFile));
		foreach ($txt as $line)
		{
			$cells = explode("\t", $line);
			if (count($cells) < 2) continue;
			$json[$cells[0]] = $cells[1];
		}
	*/
		if (!empty($json))
		{
			$coins = new db_coins;
			$dataset = $coins->findAll(array(
				'condition'=>"name=:u",
				'params'=>array(':u'=>'unknown')
			));

			if (!empty($dataset))
			foreach ($dataset as $coin) {
				if (isset($json[$coin->symbol])) {
					$name = $json[$coin->symbol];
					echo "{$coin->symbol}: {$name}\n";
					$coin->name = $name;
					$nbUpdated += $coin->save();
				}
			}
			if ($nbUpdated)
				echo "$nbUpdated coin labels updated from labels.json file\n";
		}
		return $nbUpdated;
	}

}
