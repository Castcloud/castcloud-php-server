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
?>