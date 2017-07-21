<?php 
$forms = array(
		array(
			'name'=>'ppp_admin_email',
			'title'=>'admin email',
			'action'=>'ppp_admin_email',
			'type'=>'text',
			'desc'=>'sending admin email, if empty, will use default admin email wordpress'
		),
		array(
			'name'=>'ppp_admin_code',
			'title'=>'Admin Code',
			'action'=>'ppp_admin_code',
			'type'=>'text',
			'desc'=>'Secure code to prevent stranger to accept the user'
		),
	);
?>