<?php
include 'models/token.php';

function post_login($app) {
	$username = $app -> request -> params('username');
	$password = $app -> request -> params('password');
	$clientname = $app -> request -> params('clientname');
	$clientdescription = $app -> request -> params('clientdescription');
	$clientversion = $app -> request -> params('clientversion');
	$uuid = $app -> request -> params('uuid');
	$apikey = $app -> request -> params('apikey');

	$required = array("username", "password", "clientname", "clientdescription", "uuid");
	$status = "The following parameters are missing: ";
	$missing = 0;
	foreach ($required as $key) {
		if (!array_key_exists($key, $app -> request -> params())) {
			$status .= $key . ", ";
			$missing++;
		}
	}
	$status = substr($status, 0, strlen($status) - 2);
	if ($missing > 0) {
		$app -> halt(400, $status);
	}

	$dbh = $GLOBALS['dbh'];	
	$db_prefix = $GLOBALS['db_prefix'];
	$sth = $dbh -> query("SELECT * FROM {$db_prefix}users WHERE username='$username'");
	if ($sth) {
		if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
			$userid = $result['UserID'];
			if (password_verify($password,$result['Password'])) {
				$sth = $dbh -> query("SELECT * FROM {$db_prefix}client WHERE Name='$clientname'");
				if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
					$clientid = $result['ClientID'];
				}
				else {
					$sth = $dbh -> prepare("INSERT INTO {$db_prefix}client (name) VALUES(:clientname)");
					$sth -> bindParam(':clientname', $clientname, PDO::PARAM_STR);
					$sth -> execute();

					$clientid = $dbh->lastInsertId();
				}

				$sth = $dbh -> query("SELECT * FROM {$db_prefix}clientauthorization WHERE userid=$userid AND clientid=$clientid AND uuid='$uuid' AND StatusID > 0");
				if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
					$token = $result['Token'];
				}
				else {
					$token = base64_encode(random_bytes(32));

					$sth = $dbh->query("SELECT token FROM {$db_prefix}clientauthorization");
					if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
						while (in_array($token, $result)) {
							$token = base64_encode(random_bytes(32));
						}
					}

					$sth = $dbh -> prepare("INSERT INTO {$db_prefix}clientauthorization (userid, clientid, token, clientdescription, statusid, clientversion, uuid, seents) "
					 . "VALUES($userid, $clientid, '$token', :clientdescription, 1, :clientversion, :uuid, " . time() . ")");
					$sth -> bindParam(':clientdescription', $clientdescription, PDO::PARAM_STR);
					$sth -> bindParam(':clientversion', $clientversion, PDO::PARAM_STR|PDO::PARAM_NULL);
					$sth -> bindParam(':uuid', $uuid, PDO::PARAM_STR);
					$sth -> execute();
				}

				json(new token($token));
			} else {
				$app -> halt(400, 'Invalid username and password combination');
			}
		} else {
			$app -> halt(400, 'Invalid username and password combination');
		}
	} else {
		$error = $dbh -> errorInfo();
		error_log("Castcloud database error: " . $error[2], 0);
		$app -> halt(500, 'Server error');
	}
}
?>
