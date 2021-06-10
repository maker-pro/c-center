<?php

require './conf.php';

$link = new mysqli($servername, $username, $password, $database);

echo '<---start time:' . date('Y-m-d H:i:s') . '--->';

// 每组url 数量
$max_num = 10;

// active domain list
$domains = activeDomain();
$clients = activeClient();

while ($urls = urlGroupByDomain($domains)) {
	$task_list = taskGrooup($urls);
	echo "\nbatch --->" . count($task_list) . "\n";
	if ($task_list) {
		insertTask($clients, $task_list);	
	}
}


echo '<---end time:' . date('Y-m-d H:i:s') . '--->';

function activeClient()
{
	$link = $GLOBALS['link'];
	$sql = 'SELECT ClientID FROM db_client WHERE IsActive = "YES";';
	$res = $link->query($sql);

	while ($row = $res->fetch_assoc()) {
		$clients[] = $row['ClientID'];
	}

	return $clients;
}

function activeDomain()
{
	$link = $GLOBALS['link'];
	$sql = 'SELECT domain FROM db_domains WHERE IsActive = "ACTIVE" AND ShowRpt = "YES";';
	$res = $link->query($sql);

	while ($row = $res->fetch_assoc()) {
		$domains[] = $row['domain'];
	}

	return $domains;
}

function urlGroupByDomain($domains)
{
	$link = $GLOBALS['link'];
	$max_num = $GLOBALS['max_num'];

	$sql = 'SELECT * FROM `db_crawler_urls` WHERE `Status` = "NEW" ORDER BY LastUpdateTime ASC LIMIT 1000;';
	$res = $link->query($sql);

	$domain_group_arr = array();
	if ($res) {
		while ($row = $res->fetch_assoc()) {
			if (in_array($row['Domain'], $domains)) {
				$domain_group_arr[$row['Domain']][] = $row;
			}
		}	
	}

	return $domain_group_arr;
}

function taskGrooup(&$urls)
{
	$link = $GLOBALS['link'];
	$domain_keys = array_keys($urls);
	$tmp_task_list = array();
	$task_list = array();

	while (!empty($urls)) {
		foreach ($domain_keys as $key => $domain) {
			if (isset($urls[$domain]) && !empty($urls[$domain])) {
				$tmp_task_list[] = array_pop($urls[$domain]);
				if (count($tmp_task_list) == 10) {
					$task_list[] = $tmp_task_list;
					$tmp_task_list = array();
				}
			} else {
				unset($urls[$domain]);
				unset($domain_keys[$key]);
			}
		}   
	}

	return $task_list;
}

function insertTask($clients, $task_list)
{
	$link = $GLOBALS['link'];
	$rand_max = count($clients) - 1;
	$date = date('Y-m-d H:i:s');

	$url_ids = array();

	foreach($task_list as $task_item) {
		$client_id = $clients[mt_rand(0, $rand_max)];
		$task_detail = array();
		foreach($task_item as $item) {
			$url_ids[$client_id][] = $item['ID'];
			$task_type = $item['TaskType'];
			$task_detail[$item['ID']] = array(
				'url_id' => $item['ID'],
				'url' => $item['Url'],
				'request_header' => $item['RequestHeader'],
				'request_post' => $item['RequestPost'],
			);
		}

		if ($task_detail) {
			$task_detail = json_encode($task_detail);
			$sql = 'insert into db_crawler_task (TaskType, AddTime, ClientId, TaskList) ';
			$sql .= ' values ("'.$task_type.'", "'.$date.'", "'.$client_id.'", "'.addslashes($task_detail).'");';
			$link->query($sql);
		}
	}

	if ($url_ids) {
		foreach($url_ids as $client_id => $ids) {
			$sql = 'update db_crawler_urls set Status = "PROCESS", ClientId = "' . $client_id . '" where ID in (' . implode(',', $ids) . ');';
			$link->query($sql);
		}
	}

	return true;
}