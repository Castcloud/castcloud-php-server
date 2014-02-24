<?php
class AuthMiddleware extends \Slim\Middleware {
	public function call() {
		if ($this->app->request->getResourceUri() == '/account/login') {
			$this->next->call();
		}
		else {
			$doAuth = function() {
				if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
					$userid = $this->app->request->params('userid');

					$sth = $GLOBALS['dbh']->query("SELECT * FROM clientauthorization WHERE userid=$userid");
					if ($sth) {
						$result = $sth->fetch(PDO::FETCH_ASSOC);
						if ($result['Tolken'] != $_SERVER['HTTP_AUTHORIZATION']) {
							json(array("status" => "Bad token"));
							$this->app->stop();
						}
					}
					else {
						json(array("status" => "Auth failed"));
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