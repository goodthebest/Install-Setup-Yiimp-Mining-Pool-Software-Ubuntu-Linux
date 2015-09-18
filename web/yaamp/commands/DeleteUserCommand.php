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

			echo "Yii deleteuser command\n";
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
		return parent::getHelp().'deleteuser';
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

		$nbDeleted = 0;

		$users = new db_accounts;

		$user = $users->find(array('condition'=>'id=:id', 'params'=>array(':id'=>$id)));
		if ($user && $user->id == $id)
		{
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
