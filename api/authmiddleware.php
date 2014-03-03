<?php
class AuthMiddleware extends \Slim\Middleware {
	public function call() {
		$doAuth = function() {
			if ($this->app->request->getResourceUri() == '/account/login') {
				return;
			}
			else {
				if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
					$token = $_SERVER['HTTP_AUTHORIZATION'];
					$sth = $GLOBALS['dbh']->query("SELECT * FROM clientauthorization WHERE token='$token'");
					if ($sth && $sth->rowCount() < 1) {
						json(array("status" => "Bad token"));
						$this->app->stop();
					} else {
						$time = time();
						$result = $sth->fetch(PDO::FETCH_ASSOC);

						$this->app->userid = $result['UserID'];
						$this->app->clientid = $result['ClientID'];
						$this->app->uniqueclientid = $result['UniqueClientID'];
						$this->app->clientdescription = $result['ClientDescription'];

						$sth = $GLOBALS['dbh']->query("SELECT * FROM client WHERE clientid=".$result['ClientID']);
						if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
							$this->app->clientname = $result['Name'];
						}						

						$sth = $GLOBALS['dbh'] -> prepare("UPDATE clientauthorization SET SeenTS=:time WHERE UniqueClientID=:UniqueClientID");
						$sth -> bindParam(':time', $time, PDO::PARAM_INT);
						$sth -> bindParam(':UniqueClientID', $result["UniqueClientID"], PDO::PARAM_INT);
						$sth -> execute();
					}
				}
				else {
					json(array("status" => "No token"));
					$this->app->stop();
				}
			}
		};

		$this->app->hook('slim.before.dispatch', $doAuth);
		
		$this->next->call();
	}
}
?>
