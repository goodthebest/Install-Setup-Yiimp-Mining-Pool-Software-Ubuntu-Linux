<?php

class BenchController extends CommonController
{
	public $defaultAction='index';

	/////////////////////////////////////////////////

	public function actionIndex()
	{
		$algo = substr(getparam('algo'), 0, 32);
		if ($algo) {
			$a = dboscalar('SELECT count(id) FROM benchmarks WHERE algo LIKE :algo', array(':algo'=>$algo));
			user()->setState('bench-algo', $a ? $algo : 'all');
			//$this->redirect('/bench');
			//return;
		} else {
			$algo = user()->getState('bench-algo');
		}
		$this->render('index', array('algo'=>$algo));
	}

	public function actionResults_overall()
	{
		$this->renderPartial('results_overall');
	}

	public function actionDel()
	{
		$id = getiparam('id');
		if ($id > 0) {
			dborun("DELETE FROM benchmarks WHERE id=$id");
		}
		$this->goback();
	}
}
