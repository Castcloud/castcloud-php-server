<?php
/**
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   swaggerVersion="1.2",
 *   basePath="http://api.castcloud.org/api",
 *   resourcePath="/account",
 *   description="Account related operations",
 *   produces="['application/json','application/xml']"
 * )
 * @SWG\Api(
 * 	path="/account/login",
 * 	description="User login.",
 * 	@SWG\Operation(
 * 		method="POST",
 * 		nickname="Login",
 * 		summary="Get access token",
 * 		type="Herp",
 * 		@SWG\Parameter(
 * 			name="username",
 * 			description="Users username",
 * 			paramType="form",
 * 			required=true,
 * 			type="string"
 * 		),
 * 		@SWG\Parameter(
 * 			name="password",
 * 			description="Users password",
 * 			paramType="form",
 * 			required=true,
 * 			type="string"
 * 		),
 * 		@SWG\Parameter(
 * 			name="clientname",
 * 			description="Client Name",
 * 			paramType="form",
 * 			required=true,
 * 			type="string"
 * 		),
 * 		@SWG\Parameter(
 * 			name="clientdescription",
 * 			description="Client Description. e.g. Sallys iPad",
 * 			paramType="form",
 * 			required=true,
 * 			type="string"
 * 		),
 * 		@SWG\Parameter(
 * 			name="clientversion",
 * 			description="Client Version",
 * 			paramType="form",
 * 			required=true,
 * 			type="string"
 * 		),
 * 		@SWG\Parameter(
 * 			name="uuid",
 * 			description="Universally unique identifier. String used for uniqely identifying an instanse of an application.",
 * 			paramType="form",
 * 			required=true,
 * 			type="string"
 * 		),
 * 		@SWG\Parameter(
 * 			name="apikey",
 * 			description="Clients official apikey. Not yet implemented",
 * 			paramType="form",
 * 			required=false,
 * 			type="string"
 * 		)
 * 	)
 * )
 */
function post_login($app) {
	$username = $app -> request -> params('username');
	$password = $app -> request -> params('password');
	$clientname = $app -> request -> params('clientname');
	$clientdescription = $app -> request -> params('clientdescription');
	$clientversion = $app -> request -> params('clientversion');
	$uuid = $app -> request -> params('uuid');
	$apikey = $app -> request -> params('apikey');

	$required = array("username", "password", "clientname", "clientdescription", "clientversion", "uuid");
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
		json(array("status" => $status));
		$app -> stop();
	}

	$dbh = $GLOBALS['dbh'];	
	$db_prefix = $GLOBALS['db_prefix'];
	$sth = $dbh -> query("SELECT * FROM {$db_prefix}users WHERE username='$username'");
	if ($sth) {
		if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
			$userid = $result['UserID'];
			$salt = $result['Salt'];
			if ($result['Password'] == md5($password . $salt)) {
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

				$sth = $dbh -> query("SELECT * FROM {$db_prefix}clientauthorization WHERE userid=$userid AND clientid=$clientid AND uuid='$uuid'");
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

					$sth = $dbh -> prepare("INSERT INTO {$db_prefix}clientauthorization (userid, clientid, token, clientdescription, clientversion, uuid, seents) "
					 . "VALUES($userid, $clientid, '$token', :clientdescription, :clientversion, :uuid, " . time() . ")");
					$sth -> bindParam(':clientdescription', $clientdescription, PDO::PARAM_STR);
					$sth -> bindParam(':clientversion', $clientversion, PDO::PARAM_STR);
					$sth -> bindParam(':uuid', $uuid, PDO::PARAM_STR);
					$sth -> execute();
				}

				json(array("token" => $token));
			} else {
				json(array("status" => "Login failed"));
			}
		} else {
			json(array("status" => "Login failed"));
		}
	} else {
		$error = $dbh -> errorInfo();
		error_log("Castcloud database error: " . $error[2], 0);
		json(array("status" => "Database fail"));
	}
}
?>
