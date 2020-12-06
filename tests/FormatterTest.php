<?php
declare(strict_types=1);

namespace Iqomp\Locale\Tests;

use PHPUnit\Framework\TestCase;
use Iqomp\Locale\Locale;
use Iqomp\Formatter\Formatter;

final class FormatterTest extends TestCase
{
    public function testFormat(): void
    {
        Locale::reset();
        Locale::addLocaleDir(__DIR__ . '/locale/main01');
        Locale::setLanguage('en');

        $format = [
            'user' => [
                'type' => 'json'
            ],
            'greeting' => [
                'type' => 'locale'
            ]
        ];
        $object = (object)[
            'user' => json_encode([
                'name' => [
                    'first' => 'Indi',
                    'last' => 'Khan'
                ]
            ]),
            'greeting' => Locale::encode('Hallo name', [
                'title' => 'Mr',
                'name'  => '$user.name.last'
            ])
        ];

        $res = Formatter::formatApply($format, [$object]);
        $res = $res[0];

        $expect = 'Hallo Mr. Khan';
        $this->assertEquals($expect, $res->greeting);
    }
}
