<?php
declare(strict_types=1);

namespace Iqomp\Tests;

use PHPUnit\Framework\TestCase;
use Iqomp\Locale\Locale;

final class LocaleTest extends TestCase
{
    public function testAddLocaleDir(): void {
        Locale::reset();
        Locale::addLocaleDir(__DIR__ . '/locale/main01');
        Locale::setLanguage('id');

        $this->assertEquals('Bukan Binari', Locale::translate('Non Binary'));
    }

    public function testFetchTranslation(): void {
        Locale::reset();
        Locale::addLocaleDir(__DIR__ . '/locale/main01');
        Locale::setLanguage('id');

        $this->assertEquals('Perempuan', Locale::translate('Female'));
    }

    public function testGetAllLanguages(): void {
        Locale::reset();
        Locale::addLocaleDir(__DIR__ . '/locale/main01');
        Locale::addLocaleDir(__DIR__ . '/locale/main02');

        $this->assertArrayHasKey('en-UK', Locale::getAllLanguages());
    }

    public function testGetCacheDir(): void{
        $dir = __DIR__ . '/locale/main00';
        Locale::setCacheDir($dir);
        $this->assertEquals($dir, Locale::getCacheDir());
    }

    public function testGetLanguage(): void{
        Locale::setLanguage('en-US');

        $this->assertEquals('en-US', Locale::getLanguage());
    }

    public function testSetLanguage(): void {
        Locale::setLanguage('en-US');

        $this->assertEquals('en-US', Locale::getLanguage());
    }

    public function testSetLanguageShort(): void {
        Locale::setLanguage('id');

        $this->assertEquals('id-ID', Locale::getLanguage());
    }

    public function testSetLanguageNonExists(): void {
        $lang = Locale::getLanguage();
        Locale::setLanguage('ux');

        $this->assertEquals($lang, Locale::getLanguage());
    }

    public function testTranslate(): void {
        Locale::setLanguage('en-US');

        $result = Locale::translate('We have x item', ['itm'=>1]);
        $trans  = 'We have 1 item';

        $this->assertEquals($trans, $result);
    }
}
