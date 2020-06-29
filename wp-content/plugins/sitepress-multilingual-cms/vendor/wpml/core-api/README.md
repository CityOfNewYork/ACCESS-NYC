# WPML Core API

## Table of Contents

* [PostTranslations](#posttranslations)
    * [setAsSource](#setassource)
    * [setAsTranslationOf](#setastranslationof)
    * [get](#get)
    * [getIfOriginal](#getiforiginal)
* [Translations](#translations)
    * [setLanguage](#setlanguage)
    * [setAsSource](#setassource-1)
    * [setAsTranslationOf](#setastranslationof-1)
    * [get](#get-1)
    * [getIfOriginal](#getiforiginal-1)
    * [isOriginal](#isoriginal)

## PostTranslations

Class PostTranslations



* Full name: \WPML\Element\API\PostTranslations


### setAsSource



```php
PostTranslations::setAsSource( mixed $...$el_id, mixed $...$language_code ): callable|integer
```

- Curried :: int → string → void

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$language_code` | **mixed** |  |




---

### setAsTranslationOf



```php
PostTranslations::setAsTranslationOf( mixed $...$el_id, mixed $...$translated_id, mixed $...$language_code ): callable|integer
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$translated_id` | **mixed** |  |
| `$...$language_code` | **mixed** |  |




---

### get



```php
PostTranslations::get( mixed $...$el_id ): callable|array
```

- Curried :: int → [object]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |




---

### getIfOriginal



```php
PostTranslations::getIfOriginal( mixed $...$el_id ): callable|array
```

- Curried :: int → [object]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |




---

## Translations

Class Translations



* Full name: \WPML\Element\API\Translations


### setLanguage



```php
Translations::setLanguage( mixed $...$el_id, mixed $...$el_type, mixed $...$trid, mixed $...$language_code, mixed $...$src_language_code, mixed $...$check_duplicates ): callable|integer
```

- Curried

         Wrapper function for SitePress::set_element_language_details

- int         $el_id the element's ID (for terms we use the `term_taxonomy_id`)
- string      $el_type
- int         $trid
- string      $language_code
- null|string $src_language_code
- bool        $check_duplicates

returns bool|int|null|string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$el_type` | **mixed** |  |
| `$...$trid` | **mixed** |  |
| `$...$language_code` | **mixed** |  |
| `$...$src_language_code` | **mixed** |  |
| `$...$check_duplicates` | **mixed** |  |




---

### setAsSource



```php
Translations::setAsSource( mixed $...$el_id, mixed $...$el_type, mixed $...$language_code ): callable|integer
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$el_type` | **mixed** |  |
| `$...$language_code` | **mixed** |  |




---

### setAsTranslationOf



```php
Translations::setAsTranslationOf( mixed $...$el_id, mixed $...$el_type, mixed $...$translated_id, mixed $...$language_code ): callable|integer
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$el_type` | **mixed** |  |
| `$...$translated_id` | **mixed** |  |
| `$...$language_code` | **mixed** |  |




---

### get



```php
Translations::get( mixed $...$el_id, mixed $...$el_type ): callable|array
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$el_type` | **mixed** |  |




---

### getIfOriginal



```php
Translations::getIfOriginal( mixed $...$el_id, mixed $...$el_type ): callable|array
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$el_type` | **mixed** |  |




---

### isOriginal



```php
Translations::isOriginal( mixed $...$el_id, mixed $...$translations ): callable|boolean
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$el_id` | **mixed** |  |
| `$...$translations` | **mixed** |  |




---



--------
> This document was automatically generated from source code comments on 2020-05-19 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
