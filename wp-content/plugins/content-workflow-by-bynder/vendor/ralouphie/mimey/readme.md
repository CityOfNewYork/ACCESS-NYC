Mimey
=====

PHP package for converting file extensions to MIME types and vice versa.

[![Build Status](https://travis-ci.org/ralouphie/mimey.svg?branch=master)](https://travis-ci.org/ralouphie/mimey)
[![Coverage Status](https://coveralls.io/repos/ralouphie/mimey/badge.svg?branch=master&service=github)](https://coveralls.io/github/ralouphie/mimey?branch=master)
[![Code Climate](https://codeclimate.com/github/ralouphie/mimey/badges/gpa.svg)](https://codeclimate.com/github/ralouphie/mimey)
[![Latest Stable Version](https://poser.pugx.org/ralouphie/mimey/v/stable.png)](https://packagist.org/packages/ralouphie/mimey)
[![Latest Unstable Version](https://poser.pugx.org/ralouphie/mimey/v/unstable.png)](https://packagist.org/packages/ralouphie/mimey)
[![License](https://poser.pugx.org/ralouphie/mimey/license.png)](https://packagist.org/packages/ralouphie/mimey)

This package uses [httpd][]'s [mime.types][] to generate a mapping of file extension to MIME type and the other way around.

The `mime.types` file is parsed by `bin/generate.php` and converted into an optimized PHP array in `mime.types.php`
which is then wrapped by helper class `\Mimey\MimeTypes`.

[httpd]: https://httpd.apache.org/docs/current/programs/httpd.html
[mime.types]: https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types

## Usage

```php
$mimes = new \Mimey\MimeTypes;

// Convert extension to MIME type:
$mimes->getMimeType('json'); // application/json

// Convert MIME type to extension:
$mimes->getExtension('application/json'); // json
```

### Getting All

It's rare, but some extensions have multiple MIME types:

```php
// Get all MIME types for an extension:
$mimes->getAllMimeTypes('wmz'); // array('application/x-ms-wmz', 'application/x-msmetafile')
```

However, there are many MIME types have multiple extensions:

```php
// Get all extensions for a MIME type:
$mimes->getAllExtensions('image/jpeg'); // array('jpeg', 'jpg', 'jpe')
```

## Install

Compatible with PHP >= 5.3.

```
composer require ralouphie/mimey
```
