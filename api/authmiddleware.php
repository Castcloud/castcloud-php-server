<?php
class AuthMiddleware extends \Slim\Middleware {
	public function call() {
		$doAuth = function() {
			$db_prefix = $GLOBALS['db_prefix'];
			if ($this->app->request->getResourceUri() == '/account/login') {
				return;
			}
			else {
				if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
					$token = $_SERVER['HTTP_AUTHORIZATION'];
					$sth = $GLOBALS['dbh']->query("SELECT
						auth.*,
						users.Username,
						users.Mail
						FROM
						{$db_prefix}clientauthorization AS auth,
						{$db_prefix}users AS users
						WHERE
						token='$token'
						AND auth.UserID = users.UserID");
					if ($sth && $sth->rowCount() < 1) {
						$this -> app -> halt(400, 'Bad token');
					} else {
						$time = time();
						$result = $sth->fetch(PDO::FETCH_ASSOC);

						$this->app->userid = $result['UserID'];
						$this->app->clientid = $result['ClientID'];
						$this->app->username = $result['Username'];
						$this->app->mailaddress = $result['Mail'];
						$this->app->uniqueclientid = $result['UniqueClientID'];
						$this->app->clientdescription = $result['ClientDescription'];

						$sth = $GLOBALS['dbh']->query("SELECT * FROM {$db_prefix}client WHERE clientid=".$result['ClientID']);
						if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
							$this->app->clientname = $result['Name'];
						}						

						$sth = $GLOBALS['dbh'] -> prepare("UPDATE {$db_prefix}clientauthorization SET SeenTS=:time WHERE Token=:token");
						$sth -> bindParam(':time', $time);
						$sth -> bindParam(':token', $token);
						$sth -> execute();
					}
				}
				else {
					$this -> app -> halt(400, 'Bad token');
				}
			}
		};

		$this->app->hook('slim.before.dispatch', $doAuth);

		$this->next->call();
	}
}
?>
