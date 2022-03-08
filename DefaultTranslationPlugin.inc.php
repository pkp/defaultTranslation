<?php

/**
 * @file DefaultTranslationPlugin.inc.php
 *
 * Copyright (c) 2013-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @package plugins.generic.defaultTranslation
 * @class DefaultTranslationPlugin
 *
 * Fallbacks to an English translation if the requested locale key isn't translated for the current locale
 */

use PKP\facades\Locale;
use PKP\i18n\interfaces\LocaleInterface;
use PKP\plugins\GenericPlugin;
use PKP\plugins\HookRegistry;

class DefaultTranslationPlugin extends GenericPlugin
{
    /**
     * @copydoc PKPPlugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.defaultTranslation.displayName');
    }

    /**
     * @copydoc PKPPlugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.defaultTranslation.description');
    }

    /**
     * @copydoc LazyLoadPlugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            HookRegistry::register('Locale::translate', fn () => $this->translate(...func_get_args()));
        }
        return $success;
    }

    /**
     * @copydoc PKPPlugin::getSeq()
     */
    public function getSeq(): int
    {
        return -1;
    }

    /**
     * Attempts to find a translation for the requested locale key using the default locale (en_US)
     */
    public function translate(string $hookName, array $args): bool
    {
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
