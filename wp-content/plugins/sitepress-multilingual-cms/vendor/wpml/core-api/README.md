# WPML Core API

## Table of Contents

* [IfOriginalPost](#iforiginalpost)
    * [getTranslations](#gettranslations)
    * [getTranslationIds](#gettranslationids)
* [Languages](#languages)
    * [getActive](#getactive)
    * [getFlagUrl](#getflagurl)
    * [withFlags](#withflags)
    * [getAll](#getall)
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

## IfOriginalPost





* Full name: \WPML\Element\API\IfOriginalPost


### getTranslations

Gets the element details for the translations of the given post id.

```php
IfOriginalPost::getTranslations( integer $id = null ): \WPML\Collect\Support\Collection|callable
```

Returns an empty array if the id is not an original post.

element details structure:
```php
(object) [
 'original' => false,            // bool True if the element is the original, false if a translation
 'element_id' => 123,            // int The element id
 'source_language_code' => 'en', // string The source language code
 'language_code' => 'de',        // string The language of the element
 'trid' => 456,                  // int The translation id that links translations to source.
]
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **integer** | The post id. Optional. If missing then returns a callable waiting for the id. |




---

### getTranslationIds

Get the element ids for the translations of the given post id.

```php
IfOriginalPost::getTranslationIds( integer $id = null ): \WPML\Collect\Support\Collection|callable
```

Returns an empty array if the id is not an original post.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **integer** | The post id. Optional. If missing then returns a callable waiting for the id. |




---

## Languages





* Full name: \WPML\Element\API\Languages


### getActive



```php
Languages::getActive(  ): array
```

It returns an array of the active languages.

The returned array is indexed by language code and every element has the following structure:
```
 'fr' => [
     'code'           => 'fr',
     'id'             => 3,
     'english_name'   => 'French',
     'native_name'    => 'Français',
     'major'          => 1,
     'default_locale' => 'fr_FR',
     'encode_url'     => 0,
     'tag'            => 'fr ,
     'display_name'   => 'French
 ]
```

* This method is **static**.



---

### getFlagUrl



```php
Languages::getFlagUrl( mixed $...$code ): callable|string
```

- Curried :: string → string

Gets the flag url for the given language code.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$code` | **mixed** |  |




---

### withFlags



```php
Languages::withFlags( mixed $...$langs ): callable|array
```

- Curried :: [code => lang] → [code => lang]

Adds the language flag url to the array of languages.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$langs` | **mixed** |  |




---

### getAll



```php
Languages::getAll(  ): callable|array
```

void → [lang]

It returns an array of the all the languages.

The returned array is indexed by language code and every element has the following structure:
```
 'fr' => [
     'code'           => 'fr',
     'id'             => 3,
     'english_name'   => 'French',
     'native_name'    => 'Français',
     'major'          => 1,
     'default_locale' => 'fr_FR',
     'encode_url'     => 0,
     'tag'            => 'fr ,
     'display_name'   => 'French
 ]
```

* This method is **static**.



---

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

- Curried :: int → string → int|null → string → string → string|null → bool → bool|int|null|string

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
> This document was automatically generated from source code comments on 2020-06-28 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
