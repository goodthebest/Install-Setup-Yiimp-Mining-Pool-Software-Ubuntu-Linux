<?php
/**
 * DeleteCoinCommand is a console command, to delete a coin with associated users by its db id :
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * yiic deletecoin <id>
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class DeleteCoinCommand extends CConsoleCommand
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

			echo "Yii deletecoin command\n";
			echo "Usage: yiic deletecoin <id>\n";
			return 1;

		} else {

			$id = (int) $args[0];
			$this->deleteCoin($id);
			return 0;
		}
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return parent::getHelp().'deletecoin';
	}

	/**
	 * Delete coin by id
	 */
	public function deleteCoin($id)
	{
		$modelsPath = $this->basePath.'/yaamp/models';
		if(!is_dir($modelsPath))
			echo "Directory $modelsPath is not a directory\n";

		require_once($modelsPath.'/db_coinsModel.php');

		$coins = new db_coins;

		if (!$coins instanceof CActiveRecord)
			return;

		$coin = $coins->find(array('condition'=>'id=:id', 'params'=>array(':id'=>$id)));
		if ($coin && $coin->id == $id)
		{
			$ids_query = "(SELECT id FROM accounts WHERE coinid=".$coin->id.")";

			dborun("DELETE FROM balanceuser WHERE userid IN $ids_query");
			dborun("DELETE FROM hashuser WHERE userid IN $ids_query");
			dborun("DELETE FROM shares WHERE userid IN $ids_query");
			dborun("DELETE FROM workers WHERE userid IN $ids_query");
			dborun("DELETE FROM payouts WHERE account_id IN $ids_query");

			dborun("DELETE FROM blocks WHERE coin_id=".$coin->id);
			dborun("DELETE FROM shares WHERE coinid=".$coin->id);
			dborun("DELETE FROM earnings WHERE coinid=".$coin->id);
			$nbAccounts = dborun("DELETE FROM accounts WHERE coinid=".$coin->id);

			$coin->installed=0;
			$coin->enable=0;

			$coin->save();

			echo "coin ".$coin->symbol." deleted\n";
			if ($nbAccounts)
				echo " with $nbAccounts accounts\n";

			$coin->delete();
		}
	}

}
