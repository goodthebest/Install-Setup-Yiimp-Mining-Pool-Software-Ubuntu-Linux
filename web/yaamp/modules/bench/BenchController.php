<?php

class BenchController extends CommonController
{
	public $defaultAction='index';

	/////////////////////////////////////////////////

	public function actionIndex()
	{
		$this->render('index');
	}

	public function actionAlgo()
	{
		$algo = substr(getparam('algo'), 0, 32);
		$a = dboscalar('SELECT count(id) FROM benchmarks WHERE algo LIKE :algo', array(':algo'=>$algo));

		if($a)
			user()->setState('bench-algo', $algo);
		else
			user()->setState('bench-algo', 'all');

		$this->goback();
	}

	public function actionResults_overall()
	{
		$this->renderPartial('results_overall');
	}

}

