<?php
function random_bytes($n) {	
	$bytes = "";
	for ($i = 0; $i < $n; $i++) {
		$bytes.=chr(rand(0, 255));
	}
	return $bytes;
}

function json($json) {
	$GLOBALS['app']->response->header('Content-Type', 'application/json');
	echo json_encode($json);
}

function opml($opml) {
	$GLOBALS['app']->response->header('Content-Type', 'text/x-opml');
	include 'templates/opml.pxml';
}

function superexplode($csv, $delimiter = ","){
	// explode and clear " " and ""
	$csv = explode($delimiter, $csv);
	$csv = array_map('trim', $csv);
	$csv = array_diff($csv, array(''));
	return $csv;
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle)
{
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}
?>