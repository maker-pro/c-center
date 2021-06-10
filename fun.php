<?php

// error msg
define('SUCCCODE', 2000);

define('NOTASKCODE', 5001);
define('NOTASKMSG', 'NoTask');

define('NOCLIENTIDCODE', 5002);
define('NOCLIENTIDMSG', 'NoClientId');


function returnInfo($code, $msg)
{
	return array(
		'msgCode' => $code,	// 没有数据
		'msg'	  => $msg
	);	
}