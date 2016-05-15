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
			$this->redirect('/bench');
			return;
		}
		$this->render('index');
	}

	public function actionResults_overall()
	{
		$this->renderPartial('results_overall');
	}

}
