# iqomp/locale

Simple module text translation. This module catch all translation text from all
installed composer module with special rule, and make them globally available to
use. The purpose of this module is to make it possible each composer module bring
their own translation text, and overwrite by application without editing module
translation data.

The cache of language is generated during `composer update`. You can call
`composer update` anytime after updating translation file.

## Installation

```bash
composer require iqomp/locale
```

## Configuration

If your composer module has translation, add config as below on the composer.json
file of the module:

```json
{
    "extra": {
        "iqomp/locale": "iqomp/locale/"
    }
}
```

The extra key should be `iqomp/locale`, with string value pointing to a folder in
module main directory.

All php file inside the folder will be taken and combine with one application make
and make them usable globally.

The file name will be used as translation domain.

The `extra->iqomp/locale` from application `composer.json` will also loaded.

## Translation File

Each translation file should return array of the translation, where the filename
is the translation domain, array key is translation key, and the value is translation
result with format [ICU MessageFormat](https://www.php.net/manual/en/class.messageformatter.php).

```php
<?php
// dududu.php

return [
    'Thankyou' => 'Thank you!',
    'Hi name'  => 'Hi {name}!',
    'x item'   => '{qty, number} {qty, plural, =0{item}=1{item}other{items}}'
];
```

## Usage

Use class `Iqomp\Locale\Locale` to translate the text as below:

```php
<?php
use Iqomp\Locale\Locale;

// optional
Locale::setLanguage('en-US', 'en-UK', 'en');

$res = Locale::translate('Thankyou');
$res = Locale::translate('Hi name', ['name'=>'Iqbal']);
$res = Locale::translate('x item', ['qty'=>12], 'dududu');
```

## Directory Structure

Each translation should follow directory structure as below:

```
[locale/main/path]/
    [locale-NAME]/
        [domain].php
    en-US/
        gender.php
        ...
    id-ID/
        gender.php
        ...
```

## Method

### static addLocaleDir(string $path): void

Add custom locale folder to include in translation on the fly. This method make
it possible to include dynamic dir in translation database. But it's not recommended
as each translation in the folder will not be cached. If your application has
translation, add the folder to your application `composer.json` instead.

### static fetchTranslation(): void

Re-fetch all translation from cache or from additional locale dir with current
active language.

### static getAllLanguages(): array

Get all usable language defined by module or application, from cache and additional
locale dir.

### static getCacheDir(): string

Get currently used translation cache.

### static getLanguage(): ?string

Get current used translation language.

### static setLanguage(): void

Set active language. This method accept multiple argument that will test for
existential of language. If you never call this method, the language will be taken
from request header `HTTP_ACCEPT_LANGUAGE`.

### static translate(string $text, array $params=[], string $domain=null): string

Translate the text. If `domain` is null, it will use random domain.


## Unit Test

Run below script to run unit test:

```bash
composer test
```

## Linter

Run below script to run psr-12 linter:

```php
composer lint
```
