<?php
/**
 * CheckupCommand is a console command, to double check the site requirements :
 *  - directories rights
 *  - stored procedures
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * php protected/yiic.php checkup
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class CheckupCommand extends CConsoleCommand
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

		if (isset($args[0])) {

			echo "Yii checkup command\n";
			echo "Usage: yiic checkup\n";
			return 1;

		} else {

			self::checkDirectories();
			//self::checkStoredProcedures();
			self::checkModels();

			echo "ok\n";
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

	private function isDirWritable($dir)
	{
		if (!is_writable($dir)) {
			echo "directory $dir is not writable!\n";
		}
	}

	/**
	 * Vérifie les répertoires nécessitant le droit d'écriture
	 */
	public function checkDirectories()
	{
		$root = $this->basePath;

		//self::isDirWritable("$root/protected/data/.");
		self::isDirWritable("$root/yaamp/runtime/.");
	}

	/**
	 * Vérifie les procédures stockées
	 */
	private function callStoredProc($proc, $params=array())
	{
		$db = Yii::app()->db;
		$params = implode(',', $params);
		$command = $db->createCommand("CALL $proc($params);");
		try {
			$res = $command->execute();
			$command->cancel();
		} catch (CDbException $e) {
			return $e->getMessage();
		}
		return true;
	}

	/**
	 * Vérifie les procédures stockées
	 */
	public function checkStoredProcedures()
	{
		$procs = array();
		$procs['sp_test'] = array();

		foreach ($procs as $name => $params) {
			$res = self::callStoredProc($name, $params);
			if ($res !== true) {
				echo "$name: $res\n";
				// TODO: execute this script automatically in dev.
				// $sql = file_get_contents($this->basePath.'/protected/sql/DB_Procedures.sql');
			}
		}
	}


	/**
	 * Vérifie les modeles
	 */
	public function checkModels()
	{
		$modelsPath = $this->basePath.'/yaamp/models';

		if(!is_dir($modelsPath))
			echo "Directory $modelsPath is not a directory\n";

		$db = Yii::app()->db;
		$command = $db->createCommand("USE yaamp");
		$command->execute();
		$command->cancel();

		$files = scandir($modelsPath);
		foreach ($files as $model) {
			if ($model=="." || $model=="..")
				continue;

			require_once($modelsPath.'/'.$model);

			$table = pathinfo($model,PATHINFO_FILENAME);
			$table = str_replace('Model','',$table);

			$obj = CActiveRecord::model($table);

			try{
				$test = new $obj;
			}catch (Exception $e){
				echo "Error Model: $table \n";
				echo $e->getMessage();
				continue;
			}

			if ($test instanceof CActiveRecord){
				$test->count();
				//echo "count: $table"." - " .$test->count() ."\n";
			}
		}
	}


}
