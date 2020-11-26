<?php

/**
 * Locale processor
 * @package iqomp/locale
 * @version 1.0.0
 */

namespace Iqomp\Locale;

class Locale
{
    protected static $activeLang;
    protected static $cacheDir;
    protected static $exLocaleDirs   = [];
    protected static $supportedLangs = [];
    protected static $transTemplate  = [];
    protected static $transICU       = [];

    /**
     * Add external locale dir
     * @param string path Path to external locale dir
     * @return void
     */
    public static function addLocaleDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        self::$exLocaleDirs[] = $path;
        self::reset();
    }

    /**
     * Update language cache
     * @return void
     */
    public static function fetchTranslation(): void
    {
        if (self::$transTemplate) {
            return;
        }

        if (!self::$activeLang) {
            self::setLanguage();
        }

        $templates = [];

        // get from local cache
        $cache_dir = self::getCacheDir();
        $lang_file = $cache_dir . '/' . self::$activeLang . '.php';

        if (is_file($lang_file)) {
            $templates = include $lang_file;
        }

        if (!self::$exLocaleDirs) {
            self::$transTemplate = $templates;
            return;
        }

        foreach (self::$exLocaleDirs as $dir) {
            $lang_dir = $dir . '/' . self::$activeLang;
            if (!is_dir($lang_dir)) {
                continue;
            }

            $doms = self::scanDir($lang_dir);

            foreach ($doms as $dom) {
                if ('.php' !== substr($dom, -4)) {
                    continue;
                }

                $dom_file  = $lang_dir . '/' . $dom;
                $dom_name  = basename($dom_file, '.php');
                $dom_langs = include $dom_file;

                foreach ($dom_langs as $key => $value) {
                    if (!isset($templates[$key])) {
                        $templates[$key] = [];
                    }

                    $templates[$key][$dom] = $value;
                }
            }
        }

        self::$transTemplate = $templates;
    }

    /**
     * Get all supported languages
     * @return array list of supported language
     */
    public static function getAllLanguages(): array
    {
        if (self::$supportedLangs) {
            return self::$supportedLangs;
        }

        $result    = [];

        // get form local cache
        $cache_dir  = self::getCacheDir();
        $lang_files = self::scanDir($cache_dir);
        foreach ($lang_files as $file) {
            $name = basename($file, '.php');

            if ($name === $file) {
                continue;
            }

            $result[$name] = $name;
        }

        // get from ex locale dir.
        if (self::$exLocaleDirs) {
            foreach (self::$exLocaleDirs as $dir) {
                $dirs = self::scanDir($dir);
                foreach ($dirs as $lang) {
                    $abs_dir = $dir . '/' . $lang;
                    if (is_dir($abs_dir)) {
                        $result[$lang] = $lang;
                    }
                }
            }
        }

        // include shortname
        $final_result = [];
        foreach ($result as $name => $lang) {
            $final_result[$name] = $lang;
            if (false === strstr($name, '-')) {
                continue;
            }

            $short_name = explode('-', $name)[0];
            $final_result[$short_name] = $lang;
        }

        self::$supportedLangs = $final_result;
        return $final_result;
    }

    /**
     * Get local cache dir
     * @return string path to local cache dir
     */
    public static function getCacheDir(): string
    {
        if (self::$cacheDir) {
            return self::$cacheDir;
        }

        self::$cacheDir = dirname(__DIR__) . '/locale';

        return self::$cacheDir;
    }

    /**
     * Get current active language
     * @return string active language or null
     */
    public static function getLanguage(): ?string
    {
        if (self::$activeLang) {
            return self::$activeLang;
        }

        $all_langs = self::getAllLanguages();
        $headers   = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? reset($all_langs);
        $req_langs = explode(',', $headers);

        foreach ($req_langs as $lang) {
            $lang = explode(';', $lang)[0];
            if (isset($all_langs[$lang])) {
                self::$activeLang = $all_langs[$lang];
                break;
            }
        }

        return self::$activeLang;
    }

    /**
     * Reset all self cached locale data
     */
    public static function reset(): void
    {
        self::$supportedLangs = [];
        self::$transTemplate  = [];
        self::$transICU       = [];
    }

    /**
     * Set manually active language
     * @param string ...$lang
     * @return void
     */
    public static function setLanguage(): void
    {
        self::reset();

        $all_langs = self::getAllLanguages();
        $func_args = func_get_args();

        foreach ($func_args as $arg) {
            if (isset($all_langs[$arg])) {
                self::$activeLang = $all_langs[$arg];
                break;
            }
        }

        if (!self::$activeLang) {
            self::getLanguage();
        }
    }

    /**
     * Manually set local cache dir
     * @param string dir
     * @return void
     */
    public static function setCacheDir(string $dir): void
    {
        self::$cacheDir = $dir;
        self::reset();
    }

    /**
     * Scan directory for files
     * @param string path
     * @return array list of file inside the dir
     */
    public static function scanDir(string $dir): array
    {
        $files = scandir($dir);
        $files = array_diff($files, ['.','..','.gitkeep']);

        return array_values($files);
    }

    /**
     * Translate text
     * @param string text translation key
     * @param array params list of translation params
     * @param string domain translation domain
     */
    public static function translate(
        string $text,
        array $params = [],
        string $domain = null
    ): string {
        if (!self::$transTemplate) {
            self::fetchTranslation();
        }

        $templates = self::$transTemplate[$text] ?? null;
        if (!$templates) {
            return $text;
        }

        if (!$domain) {
            $domain = array_key_first($templates);
        }

        if (!isset($templates[$domain])) {
            return $text;
        }

        $template = $templates[$domain];

        if (!isset(self::$transICU[$template])) {
            $formatter = msgfmt_create(self::$activeLang, $template);
            self::$transICU[$template] = $formatter;
        }

        $formatter = self::$transICU[$template];

        return msgfmt_format($formatter, $params);
    }
}
