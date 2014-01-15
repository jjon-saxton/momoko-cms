<?php

$email=array(
	'type'=>'smtp',
	'server'=>array(
		'auth'=>true,
		'security'=>'ssl',
		'host'=>'smtp.gmail.com',
		'port'=>465,
		'user'=>'tech-coordinator@saxton-solutions.com',
		'password'=>'Artemis&Arlette'
	),
	'header'=>array(
		'from'=>array(
			'name'=>'CCTM Admin',
			'address'=>'tech-coordinator@saxton-solutions.com',
			'readdress'=>'gerald.overmyer@unco.edu'
		)
	)
);
