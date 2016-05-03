<?php

require_once("util.php");

class ExplorerController extends CommonController
{
	public $defaultAction='index';

	public function run($actionID)
	{
		// Forward the url /explorer/BTC to the BTC block explorer
		if (!empty($actionID) && !isset($_REQUEST['id'])) {
			if (strlen($actionID) <= 5) {
				$coin = getdbosql('db_coins', "enable AND visible AND symbol=:symbol", array(
					':symbol'=>strtoupper($actionID)
				));
				if ($coin) {
					$_REQUEST['id'] = $coin->id;
					$this->forward('id');
				}
			}
		}
		return parent::run($actionID);
	}

	/////////////////////////////////////////////////

	public function actionIndex()
	{
		if(isset($_COOKIE['mainbtc'])) return;
		if(!LimitRequest('explorer')) return;

		$id = getiparam('id');
		$coin = getdbo('db_coins', $id);

		$height = getparam('height');
		if($coin && intval($height)>0)
		{
			$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
			$hash = $remote->getblockhash(intval($height));
		}

		else
			$hash = getparam('hash');

		$txid = getparam('txid');
		if($coin && !empty($txid) && ctype_alnum($txid))
		{
			$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
			$tx = $remote->getrawtransaction($txid, 1);

			$hash = arraySafeVal($tx,'blockhash');
		}

		if($coin && !empty($hash) && ctype_alnum($hash))
			$this->render('block', array('coin'=>$coin, 'hash'=>substr($hash, 0, 64)));

		else if($coin)
			$this->render('coin', array('coin'=>$coin));

		else
			$this->render('index');
	}

	public function actionId()
	{
		return $this->actionIndex();
	}

	/**
	 * Difficulty Graph
	 */
	public function actionGraph()
	{
		$id = getiparam('id');
		$coin = getdbo('db_coins', $id);
		if ($coin)
			$this->renderPartial('graph', array('coin'=>$coin));
		else
			echo "[]";
	}
}
