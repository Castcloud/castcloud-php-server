<?php
class AuthMiddleware extends \Slim\Middleware {
	public function call() {
		$doAuth = function() {
			if ($this->app->request->getResourceUri() == '/install' || $this->app->request->getResourceUri() == '/login' || $this->app->request->getResourceUri() == '/') {
				return;
			}
			else {
				if (!isset($_SESSION['login'])) {
					$this->app->redirect(str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]));
					$this->app->stop();
				}
			}
		};

		$this->app->hook('slim.before.dispatch', $doAuth);
		
		$this->next->call();
	}
}
?>