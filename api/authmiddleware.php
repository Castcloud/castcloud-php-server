<?php
class AuthMiddleware extends \Slim\Middleware {
	public function call() {
		if ($this->app->request->getResourceUri() == '/account/login') {
			$this->next->call();
		}
		else {
			if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
				$userid = $this->app->request->params('userid');

				include 'cc-settings.php';

				$sth = $dbh->query("SELECT * FROM clientauthorization WHERE userid=$userid");
				if ($sth) {
					$result = $sth->fetch(PDO::FETCH_ASSOC);
					if ($result['tolken'] == $_SERVER['HTTP_AUTHORIZATION']) {
						$this->next->call();
					}
				}

				$this->app->response->header('Content-Type', 'application/json');
				$json = array("status" => "Auth failed");
				echo json_encode($json);
			}
			else {
				$this->app->response->header('Content-Type', 'application/json');
				$json = array("status" => "No auth token");
				echo json_encode($json);
			}
		}
	}
}
?>