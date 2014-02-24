<?php
class AuthMiddleware extends \Slim\Middleware {
	public function call() {
		if ($this->app->request->getResourceUri() == '/account/login') {
			$this->next->call();
		}
		else {
			if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
				$userid = $this->app->request->params('userid');

				$sth = $GLOBALS['dbh']->query("SELECT * FROM clientauthorization WHERE userid=$userid");
				if ($sth) {
					$result = $sth->fetch(PDO::FETCH_ASSOC);
					if ($result['Tolken'] == $_SERVER['HTTP_AUTHORIZATION']) {
						$this->next->call();
					}
					else {
						json(array("status" => "Bad token"));
					}
				}
				else {
					json(array("status" => "Auth failed"));				
				}
			}
			else {
				json(array("status" => "No token"));
			}
		}
	}
}
?>