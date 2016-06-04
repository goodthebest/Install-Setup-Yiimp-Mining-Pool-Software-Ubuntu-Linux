<?php

class db_bench_chips extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'bench_chips';
	}

	public function rules()
	{
		return array(
			array('chip,vendorid', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
		);
	}

	public function attributeLabels()
	{
		return array(
		);
	}

}

