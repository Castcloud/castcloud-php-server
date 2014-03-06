<?php
class InstallMiddleware extends \Slim\Middleware {
	public function call() {
		$doInstall = function() {
			if ($this->app->request->getResourceUri() == '/install') {
				return;
			}
			if (!file_exists('../api/cc-settings.php')) {
				include 'templates/install.phtml';
				$this->app->stop();
			}
		};

		$this->app->hook('slim.before.dispatch', $doInstall);

		$this->next->call();
	}
}
?>