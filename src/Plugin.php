<?php

/**
 * Composer plugin, locale builder
 * @package iqomp/locale
 * @version 1.0.1
 */

namespace Iqomp\Locale;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;

    protected static function truncateDir(string $dir): void
    {
        $files = self::scanDir($dir);
        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            if ('.gitkeep' === $file) {
                continue;
            }

            $file_abs = $dir . '/' . $file;

            if (is_dir($file_abs)) {
                self::truncateDir($file_abs);
                rmdir($file_abs);
            } else {
                unlink($file_abs);
            }
        }
    }

    protected static function scanDir(string $dir): array
    {
        return array_diff(scandir($dir), ['.','..']);
    }

    // PluginInterface
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    // PluginInterface
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    // PluginInterface
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    // EventSubscriberInterface
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'postAutoloadDump'
        ];
    }

    public static function postAutoloadDump(Event $event)
    {
        $vendor_dir     = $event->getComposer()->getConfig()->get('vendor-dir');
        $composer_dir   = $vendor_dir . '/composer';
        $installed_file = $composer_dir . '/installed.json';

        if (!is_file($installed_file)) {
            return;
        }

        $installed_json = file_get_contents($installed_file);
        $installed      = json_decode($installed_json);
        $packages       = $installed->packages;

        $locales        = [];

        // truncate exists locales
        $int_locale_path = dirname(__DIR__) . '/locale';
        if (is_dir($int_locale_path)) {
            self::truncateDir($int_locale_path);
        }

        // app composer.json file
        $app_composer_file = \Composer\Factory::getComposerFile();
        if (is_file($app_composer_file)) {
            $app_composer = file_get_contents($app_composer_file);
            $app_composer = json_decode($app_composer);
            $app_composer->{'install-path'} = dirname($app_composer_file);
            $packages[] = $app_composer;
        }

        // get all modules and app locales
        foreach ($packages as $package) {
            $locale_dir = $package->extra->{'iqomp/locale'} ?? null;
            if (!$locale_dir) {
                continue;
            }

            $install_path = $package->{'install-path'};

            $locale_dir_abs   = realpath(implode('/', [
                $composer_dir,
                $install_path,
                $locale_dir
            ]));

            if (!$locale_dir_abs) {
                $locale_dir_abs = realpath(implode('/', [
                    $install_path,
                    $locale_dir
                ]));
            }

            if (!$locale_dir_abs || !is_dir($locale_dir_abs)) {
                continue;
            }

            $languages = self::scanDir($locale_dir_abs);

            foreach ($languages as $lang) {
                $domain_dir = $locale_dir_abs . '/' . $lang;
                if (!is_dir($domain_dir)) {
                    continue;
                }

                if (!isset($locales[$lang])) {
                    $locales[$lang] = [];
                }

                $domain_files = self::scanDir($domain_dir);

                foreach ($domain_files as $file) {
                    if ('.php' !== substr($file, -4)) {
                        continue;
                    }

                    $domain = basename($file, '.php');
                    $langs  = include $domain_dir . '/' . $file;

                    foreach ($langs as $key => $value) {
                        if (!isset($locales[$lang][$key])) {
                            $locales[$lang][$key] = [];
                        }

                        $locales[$lang][$key][$domain] = $value;
                    }
                }
            }
        }

        $nl = PHP_EOL;
        foreach ($locales as $lang => $trans) {
            $lang_file = $int_locale_path . '/' . $lang . '.php';

            $tx  = '<?php' . $nl;
            $tx .= 'return ' . var_export($trans, true) . ';';

            $f = fopen($lang_file, 'w');
            fwrite($f, $tx);
            fclose($f);
        }
    }
}
