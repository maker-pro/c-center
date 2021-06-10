<?php

require __DIR__ . '/../conf.php';

$link = new mysqli($servername, $username, $password, $database);

if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
	return returnInfo(NOCLIENTIDCODE, NOCLIENTIDMSG);
}

$client_id = $_GET['client_id'];

$sql = 'select * from db_crawler_task where Status = "NEW" order by TaskId asc limit 1;';
$res = $link->query($sql);
$row = $res->fetch_assoc();

if (empty($row)) {
	return returnInfo(NOTASKCODE, NOTASKMSG);
}

$task_list = json_decode($row['TaskList'], true);
$url_ids = array_keys($task_list);

$sql = 'update db_crawler_task set Status = "PROCESS" where TaskId = ' . $row['TaskId'] . ';';
$res = $link->query($sql);
$sql = 'update db_crawler_urls set Status = "PROCESSING" where ID in (' . implode(',', $url_ids) . ');';
$res = $link->query($sql);

$task = array(
	'task_id'   => $row['TaskId'],
	'task_type' => $row['TaskType'],
	'task_list' => $task_list,
	'task_num'  => count($task_list),
	'msgCode' => SUCCCODE,
);

echo json_encode($task);
die;