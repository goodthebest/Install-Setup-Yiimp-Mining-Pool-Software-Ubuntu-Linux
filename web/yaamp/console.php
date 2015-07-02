<?php

$_SERVER["HTTP_HOST"] = YAAMP_SITE_URL;

// required in PHP 5.4
date_default_timezone_set('Europe/Paris');

return array(
	'name'=>YAAMP_SITE_URL,

	'basePath'=>YAAMP_HTDOCS."/yaamp",

	'preload'=>array('log'),
	'import'=>array('application.components.*'),

	'components'=>array(

		'request' => array(
			'hostInfo' => 'http://'.$_SERVER["HTTP_HOST"],
			'baseUrl' => '',
		),

		// autoloading model and component classes
		'import'=>array(
			'application.components.*',
			'application.commands.*',
			//'application.commands.shell.*',
			'application.models.*',
			'application.extensions.*',
		),


		'urlManager'=>array(
			'urlFormat'=>'path',
			'showScriptName'=>false,
			'appendParams'=>false,
		),

		'assetManager'=>array(
			'basePath'=>YAAMP_HTDOCS."/assets"
		),

		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
					'levels'=>'debug, trace, error, warning',
				),
//				array(
//					'class'=>'CProfileLogRoute',
//					'report'=>'summary',
//				),
			),
		),

		'user'=>array(
			'allowAutoLogin'=>true,
			'loginUrl'=>array('site/login'),
		),

		'db'=>array(
			'class'=>'CDbConnection',
			'connectionString'=>"mysql:host=".YAAMP_DBHOST.";dbname=".YAAMP_DBNAME,

			'username'=>YAAMP_DBUSER,
			'password'=>YAAMP_DBPASSWORD,

			'enableProfiling'=>false,
			'charset'=>'utf8',
			'schemaCachingDuration'=>3600,
		),
	),


);





