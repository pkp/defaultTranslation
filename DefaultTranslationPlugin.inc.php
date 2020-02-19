<?php

/**
 * @file DefaultTranslationPlugin.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @package plugins.generic.defaultTranslation
 * @class DefaultTranslationPlugin
 *
 * Display English translation if the current UI language translation doesn't exist
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class DefaultTranslationPlugin extends GenericPlugin {

	/**
	 * @copydoc PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.defaultTranslation.displayName');
	}

	/**
	 * @copydoc PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.defaultTranslation.description');
	}

	/**
	 * @copydoc LazyLoadPlugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if ($success && $this->getEnabled()) {
			HookRegistry::register('PKPLocale::translate', array($this, 'translate'));
			HookRegistry::register('PKPLocale::registerLocaleFile::isValidLocaleFile', array(&$this, 'isValidLocaleFile'));
		}
		return $success;
	}

	/**
	 * @copydoc PKPPlugin::getSeq()
	 */
	function getSeq() {
		return -1;
	}

	/**
	 * Hook callback: Handle requests.
	 * Show English translation if the current UI language translation doesn't exist.
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function translate($hookName, $args) {
		$key = $args[0];
		$params = $args[1];
		$locale = $args[2];
		$localeFiles = $args[3];
		$value =& $args[4];

		foreach ($localeFiles as $localeFile) {
			$fileName = $localeFile->getFilename();
			$newFileName = str_replace($locale, 'en_US', $fileName);
			$newFile = new LocaleFile('en_US', $newFileName);
			$value = $newFile->translate($key, $params);
			if ($value !== null) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Hook callback: Handle requests.
	 * Consider/register also the not existing locale files.
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function isValidLocaleFile($hookName, $args) {
		return true;
	}

}
