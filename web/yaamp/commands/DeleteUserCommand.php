<?php
/**
 * DeleteUserCommand is a console command, to delete an user by its db account id :
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * yiic deleteuser <id>
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class DeleteUserCommand extends CConsoleCommand
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

			echo "Yii checkup command\n";
			echo "Usage: yiic deleteuser <id>\n";
			return 1;

		} else {

			$id = (int) $args[0];
			self::deleteUser($id);
			return 0;
		}
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return parent::getHelp().'checkup';
	}

	/**
	 * Delete user by id
	 */
	public function deleteUser($id)
	{
		$modelsPath = $this->basePath.'/yaamp/models';
		if(!is_dir($modelsPath))
			echo "Directory $modelsPath is not a directory\n";

		require_once($modelsPath.'/db_accountsModel.php');

		$obj = CActiveRecord::model('db_accounts');
		$table = $obj->getTableSchema()->name;

		try{
			$users = new $obj;
		} catch (Exception $e) {
			echo "Error Model: $table \n";
			echo $e->getMessage();
			continue;
		}

		if ($users instanceof CActiveRecord)
		{
			//echo "$table: ".$users->count()." records\n";

			$nbDeleted = 0;
			foreach ($users->findAll() as $user)
			{
				if ($user->id != $id)
					continue;

				$user->balance = 0;
				dborun("DELETE FROM balanceuser WHERE userid=".$user->id);
				dborun("DELETE FROM hashuser WHERE userid=".$user->id);
				dborun("DELETE FROM shares WHERE userid=".$user->id);
				dborun("DELETE FROM workers WHERE userid=".$user->id);
				dborun("UPDATE earnings SET userid=0 WHERE userid=".$user->id);
				dborun("UPDATE blocks SET userid=0 WHERE userid=".$user->id);
				dborun("UPDATE payouts SET account_id=0 WHERE account_id=".$user->id);

				$nbDeleted += $user->delete();
			}
			echo "$nbDeleted user deleted\n";
		}
	}

}
