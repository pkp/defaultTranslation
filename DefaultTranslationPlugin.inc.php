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

use PKP\facades\Locale;
use PKP\i18n\interfaces\LocaleInterface;
use PKP\plugins\GenericPlugin;
use PKP\plugins\HookRegistry;

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
			HookRegistry::register('Locale::translate', fn () => $this->translate(...func_get_args()));
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
		[&$value, $key, $params, $number, $locale, $localeBundle] = $args;

		if ($locale === LocaleInterface::DEFAULT_LOCALE) {
			return false;
		}

		$value = $number === null
			? Locale::get($key, $params, LocaleInterface::DEFAULT_LOCALE)
			: Locale::choice($key, $number, $params, LocaleInterface::DEFAULT_LOCALE);
		return true;
	}
}
