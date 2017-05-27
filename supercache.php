<?php
/**
 * @author Norman Malessa <mail@norman-malessa.de>
 * @copyright 2017 Norman Malessa <mail@norman-malessa.de>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License, see LICENSE
 */

class plgSystemSupercache extends JPlugin {
	const SUPERCACHE_DIR = 'plg_supercache';

	/** @var int */
	private $lifetime = 0;

	/** @var bool */
	private $isCaching = false;

	/**
	 * @inheritdoc
	 */
	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
		$this->lifetime = 60 * $this->params->get('lifetime', 15);
	}

	/**
	 * @return void
	 */
	public function onAfterInitialise() {
		if ($this->isCacheAllowed()) {
			$this->isCaching = true;
			$this->initCacheDirectory();

			if ($this->isCacheHit()) {
				readfile($this->getCacheFile());
				exit;
			}
		}
	}

	/**
	 * @return void
	 */
	public function onAfterRespond() {
		$app = JFactory::getApplication();
		if ($this->isCaching) {
			file_put_contents($this->getCacheFile(), $app->toString());
		}
	}

	/**
	 * Clear defined cache routes file on article save
	 * @return void
	 */
	public function onContentAfterSave() {
		$clearRoutes = explode(',', $this->params->get('clear_routes', ''));
		if (count($clearRoutes) > 0) {
			foreach ($clearRoutes as $route) {
				$route = ltrim($route, '/');
				$uri = Juri::root() . $route;
				unlink($this->getCacheFile($uri));
			}
		}
	}

	/**
	 * @return bool
	 */
	private function isCacheAllowed() {
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		if (
			!$app->isAdmin()
			&& count($app->getMessageQueue()) === 0
			&& $user->get('guest')
			&& $input->getMethod() === 'GET'
		) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function isCacheHit() {
		if (
			is_file($this->getCacheFile())
			&& filemtime($this->getCacheFile()) > (date('U') - $this->lifetime)
		) {
			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	private function initCacheDirectory() {
		if (!is_dir($this->getCacheDirectory())) {
			mkdir($this->getCacheDirectory());
		}
	}

	/**
	 * @return string
	 */
	private function getCacheDirectory() {
		return JPATH_ROOT . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . static::SUPERCACHE_DIR;
	}

	/**
	 * @param string $uriString
	 * @return string
	 */
	private function getCacheFile($uriString = '') {
		if ($uriString == '') {
			$uri = JUri::getInstance();
			$uriString = $uri->toString();
		}
		return $this->getCacheDirectory() . DIRECTORY_SEPARATOR . md5($uriString);
	}
}
