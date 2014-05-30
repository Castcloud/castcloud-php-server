<?php
function random_bytes($n) {	
	$bytes = "";
	for ($i = 0; $i < $n; $i++) {
		$bytes.=chr(rand(0, 255));
	}
	return $bytes;
}

function json($json, $cached = false) {
	$res = json_encode($json);
	$GLOBALS['app']->response->header('Content-Type', 'application/json');
	if ($cached) {
		$GLOBALS['app']->etag(md5($res));
	}
	echo $res;
}

function opml($labels, $casts) {
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

function contentAfter($haystack, $needle)
{
    return substr($haystack, strlen($needle));
}

function get_unit_with_id($id, $array){
	foreach ($array AS $unit){
		if ($unit->id == $id){
			return $unit;
		}
	}
}

function log_db_errors($sth){
	$error = $sth->errorInfo();
	if($error[0] != "0000"){
		error_log("Castcloud database error: " . var_export($error, TRUE), 0);
		$GLOBALS['app'] -> halt(500, 'Database error'); // Yes this is brutal #toughlove
	}
}
?>