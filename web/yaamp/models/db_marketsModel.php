<?php

class db_markets extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'markets';
	}

	public function rules()
	{
		return array(
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

	public function save($runValidation=true,$attributes=null)
	{
		if (empty($this->base_coin)) $this->base_coin = null;
		if (empty($this->message)) $this->message = null;

		return parent::save($runValidation, $attributes);
	}
}

