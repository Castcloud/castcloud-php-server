<?php
class AuthMiddleware extends \Slim\Middleware {
	public function call() {
		if ($this->app->request->getResourceUri() == '/account/login') {
			$this->next->call();
		}
		else {
			$doAuth = function() {
				if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
					$token = $_SERVER['HTTP_AUTHORIZATION'];
					$sth = $GLOBALS['dbh']->query("SELECT * FROM clientauthorization WHERE tolken='$token'");
					if (!($result = $sth->fetch(PDO::FETCH_ASSOC))) {
						json(array("status" => "Bad token"));
						$this->app->stop();
					}
				}
				else {
					json(array("status" => "No token"));
					$this->app->stop();
				}
			};

			$this->app->hook('slim.before.dispatch', $doAuth);
			
			$this->next->call();
		}
	}
}
?>