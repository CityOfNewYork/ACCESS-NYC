# WPML Functional Programming Library

## Table of Contents

* [Fns](#fns)
    * [always](#always)
    * [converge](#converge)
    * [map](#map)
    * [](#)
    * [identity](#identity)
    * [tap](#tap)
    * [reduce](#reduce)
    * [reduceRight](#reduceright)
    * [filter](#filter)
    * [reject](#reject)
    * [value](#value)
    * [constructN](#constructn)
    * [ascend](#ascend)
    * [descend](#descend)
    * [useWith](#usewith)
    * [nthArg](#ntharg)
    * [either](#either)
    * [maybe](#maybe)
    * [isRight](#isright)
    * [isLeft](#isleft)
    * [isJust](#isjust)
    * [isNothing](#isnothing)
    * [T](#t)
    * [F](#f)
    * [safe](#safe)
    * [make](#make)
    * [makeN](#maken)
    * [unary](#unary)
    * [memorizeWith](#memorizewith)
    * [once](#once)
    * [withNamedLock](#withnamedlock)
    * [withoutRecursion](#withoutrecursion)
    * [liftA2](#lifta2)
    * [liftA3](#lifta3)
    * [liftN](#liftn)
    * [until](#until)
    * [init](#init)
    * [noop](#noop)
    * [maybeToEither](#maybetoeither)
* [Logic](#logic)
    * [not](#not)
    * [isNotNull](#isnotnull)
    * [ifElse](#ifelse)
    * [when](#when)
    * [unless](#unless)
    * [cond](#cond)
    * [both](#both)
    * [allPass](#allpass)
    * [anyPass](#anypass)
    * [complement](#complement)
    * [defaultTo](#defaultto)
    * [either](#either-1)
    * [](#-1)
    * [propSatisfies](#propsatisfies)
    * [](#-2)
    * [isEmpty](#isempty)
    * [init](#init-1)
* [Lst](#lst)
    * [append](#append)
    * [fromPairs](#frompairs)
    * [toObj](#toobj)
    * [pluck](#pluck)
    * [partition](#partition)
    * [sort](#sort)
    * [unfold](#unfold)
    * [zip](#zip)
    * [zipObj](#zipobj)
    * [zipWith](#zipwith)
    * [join](#join)
    * [concat](#concat)
    * [find](#find)
    * [flattenToDepth](#flattentodepth)
    * [flatten](#flatten)
    * [includes](#includes)
    * [nth](#nth)
    * [first](#first)
    * [last](#last)
    * [length](#length)
    * [take](#take)
    * [takeLast](#takelast)
    * [slice](#slice)
    * [drop](#drop)
    * [dropLast](#droplast)
    * [makePair](#makepair)
    * [](#-3)
    * [insert](#insert)
    * [range](#range)
    * [xprod](#xprod)
    * [prepend](#prepend)
    * [reverse](#reverse)
    * [init](#init-2)
    * [keyBy](#keyby)
* [Math](#math)
    * [multiply](#multiply)
    * [add](#add)
    * [product](#product)
    * [init](#init-3)
* [Obj](#obj)
    * [prop](#prop)
    * [propOr](#propor)
    * [props](#props)
    * [path](#path)
    * [pathOr](#pathor)
    * [assoc](#assoc)
    * [assocPath](#assocpath)
    * [lens](#lens)
    * [lensProp](#lensprop)
    * [lensPath](#lenspath)
    * [view](#view)
    * [set](#set)
    * [over](#over)
    * [pick](#pick)
    * [pickAll](#pickall)
    * [pickBy](#pickby)
    * [project](#project)
    * [where](#where)
    * [has](#has)
    * [evolve](#evolve)
    * [objOf](#objof)
    * [keys](#keys)
    * [values](#values)
    * [replaceRecursive](#replacerecursive)
    * [init](#init-4)
* [Relation](#relation)
    * [equals](#equals)
    * [lt](#lt)
    * [lte](#lte)
    * [gt](#gt)
    * [gte](#gte)
    * [propEq](#propeq)
    * [sortWith](#sortwith)
    * [init](#init-5)
* [Str](#str)
    * [tail](#tail)
    * [split](#split)
    * [includes](#includes-1)
    * [trim](#trim)
    * [concat](#concat-1)
    * [sub](#sub)
    * [startsWith](#startswith)
    * [pos](#pos)
    * [len](#len)
    * [replace](#replace)
    * [pregReplace](#pregreplace)
    * [match](#match)
    * [matchAll](#matchall)
    * [wrap](#wrap)
    * [init](#init-6)

## Fns





* Full name: \WPML\FP\Fns


### always



```php
Fns::always( mixed $...$a ): callable
```

Curried :: a → ( * → a )

Returns a function that always returns the given value.

```php
$t = Fns::always( 'Tee' );
$t(); //=> 'Tee'
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |




---

### converge



```php
Fns::converge( mixed $...$convergingFn, mixed $...$branchingFns ): callable
```

- Curried :: ( ( x1, x2, … ) → z ) → [( ( a, b, … ) → x1 ), ( ( a, b, … ) → x2 ), …] → ( a → b → … → z )

Accepts a converging function and a list of branching functions and returns a new function. The arity of the new function is the same as the arity of the longest branching function. When invoked, this new function is applied to some arguments, and each branching function is applied to those same arguments. The results of each branching function are passed as arguments to the converging function to produce the return value.

```php
$divide = curryN( 2, function ( $num, $dom ) { return $num / $dom; } );
$sum    = function ( Collection $collection ) { return $collection->sum(); };
$length = function ( Collection $collection ) { return $collection->count(); };

$average = Fns::converge( $divide, [ $sum, $length ] );
$this->assertEquals( 4, $average( wpml_collect( [ 1, 2, 3, 4, 5, 6, 7 ] ) ) );
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$convergingFn` | **mixed** |  |
| `$...$branchingFns` | **mixed** |  |




---

### map



```php
Fns::map( mixed $...$fn, mixed $...$target ): callable|mixed
```

- Curried :: ( a→b )→f a→f b

Takes a function and a *functor*, applies the function to each of the functor's values, and returns a functor of the same shape.

And array is considered a *functor*

Dispatches to the *map* method of the second argument, if present

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### 



```php
Fns::(  ): 
```

static callable|mixed each ( ...$fn, ...$target ) - Curried :: ( a→b )→f a→f b





---

### identity



```php
Fns::identity( mixed $mixed ): callable|mixed
```

- Curried :: a->a

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mixed` | **mixed** |  |




---

### tap



```php
Fns::tap( mixed $callable, mixed $mixed ): callable|mixed
```

- Curried :: fn->data->data

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callable` | **mixed** |  |
| `$mixed` | **mixed** |  |




---

### reduce



```php
Fns::reduce( mixed $...$fn, mixed $...$initial, mixed $...$target ): callable|mixed
```

- Curried :: ( ( a, b ) → a ) → a → [b] → a

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$initial` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### reduceRight



```php
Fns::reduceRight( mixed $...$fn, mixed $...$initial, mixed $...$target ): callable|mixed
```

- Curried :: ( ( a, b ) → a ) → a → [b] → a

Takes a function, an initial value and an array and returns the result.

The function receives two values, the accumulator and the current value, and should return a result.

The array values are passed to the function in the reverse order.

```php
$numbers = [ 1, 2, 3, 4, 5, 8, 19 ];

$append = function( $acc, $val ) {
   $acc[] = $val;
   return $acc;
};

$reducer = Fns::reduceRight( $append, [] );
$result = $reducer( $numbers ); // [ 19, 8, 5, 4, 3, 2, 1 ]

// Works on collections too.
$result = $reducer( wpml_collect( $numbers ) ); // [ 19, 8, 5, 4, 3, 2, 1 ]
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$initial` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### filter



```php
Fns::filter( mixed $...$predicate, mixed $...$target ): callable|mixed
```

- Curried :: ( a → bool ) → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### reject



```php
Fns::reject( mixed $...$predicate, mixed $...$target ): callable|mixed
```

- Curried :: ( a → bool ) → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### value



```php
Fns::value( mixed $mixed ): callable|mixed
```

- Curried :: a|( *→a ) → a

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mixed` | **mixed** |  |




---

### constructN



```php
Fns::constructN( mixed $...$argCount, mixed $...$className ): callable|object
```

- Curried :: int → string → object

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$argCount` | **mixed** |  |
| `$...$className` | **mixed** |  |




---

### ascend



```php
Fns::ascend( mixed $...$fn, mixed $...$a, mixed $...$b ): callable|integer
```

- Curried :: ( a → b ) → a → a → int

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### descend



```php
Fns::descend( mixed $...$fn, mixed $...$a, mixed $...$b ): callable|integer
```

- Curried :: ( a → b ) → a → a → int

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### useWith



```php
Fns::useWith( mixed $...$fn, mixed $...$transformations ): callable
```

- Curried :: ( ( x1, x2, … ) → z ) → [( a → x1 ), ( b → x2 ), …] → ( a → b → … → z )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$transformations` | **mixed** |  |




---

### nthArg



```php
Fns::nthArg( mixed $...$n ): callable
```

- Curried :: int → *… → *

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$n` | **mixed** |  |




---

### either



```php
Fns::either( mixed $...$f, mixed $...$g, mixed $...$e ): callable|mixed
```

- Curried:: ( a → b ) → ( b → c ) → Either a b → c

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$f` | **mixed** |  |
| `$...$g` | **mixed** |  |
| `$...$e` | **mixed** |  |




---

### maybe



```php
Fns::maybe( mixed $...$v, mixed $...$f, mixed $...$m ): callable|mixed
```

- Curried:: b → ( a → b ) → Maybe a → b

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$v` | **mixed** |  |
| `$...$f` | **mixed** |  |
| `$...$m` | **mixed** |  |




---

### isRight



```php
Fns::isRight( mixed $...$e ): callable|boolean
```

- Curried:: e → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$e` | **mixed** |  |




---

### isLeft



```php
Fns::isLeft( mixed $...$e ): callable|boolean
```

- Curried:: e → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$e` | **mixed** |  |




---

### isJust



```php
Fns::isJust( mixed $...$m ): callable|boolean
```

- Curried:: e → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$m` | **mixed** |  |




---

### isNothing



```php
Fns::isNothing( mixed $...$m ): callable|boolean
```

- Curried:: e → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$m` | **mixed** |  |




---

### T



```php
Fns::T( mixed $...$_ ): callable|mixed
```

- Curried :: _ → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$_` | **mixed** |  |




---

### F



```php
Fns::F( mixed $...$_ ): callable|mixed
```

- Curried :: _ → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$_` | **mixed** |  |




---

### safe



```php
Fns::safe( mixed $...$fn ): callable|\WPML\FP\Maybe
```

- Curried :: ( a → b ) → ( a → Maybe b )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |




---

### make



```php
Fns::make( mixed $...$className ): callable|object
```

- Curried :: string → object

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$className` | **mixed** |  |




---

### makeN



```php
Fns::makeN( mixed $...$argCount, mixed $...$className ): callable|object
```

- Curried :: int → string → object

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$argCount` | **mixed** |  |
| `$...$className` | **mixed** |  |




---

### unary



```php
Fns::unary( mixed $...$fn ): callable
```

- Curried:: ( * → b ) → ( a → b )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |




---

### memorizeWith



```php
Fns::memorizeWith( mixed $...$cacheKeyFn, mixed $...$fn ): callable|mixed
```

- Curried :: ( *… → String ) → ( *… → a ) → ( *… → a )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$cacheKeyFn` | **mixed** |  |
| `$...$fn` | **mixed** |  |




---

### once



```php
Fns::once( mixed $...$fn ): callable|mixed
```

- Curried :: ( *… → a ) → ( *… → a )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |




---

### withNamedLock



```php
Fns::withNamedLock( mixed $...$name, mixed $...$returnFn, mixed $...$fn ): callable|mixed
```

- Curried :: String → ( *… → String ) → ( *… → a ) → ( *… → a )

Creates a new function that is *locked* so that it wont be called recursively. Multiple functions can use the same lock so they are blocked from calling each other recursively

```php
     $lockName = 'my-lock';
     $addOne = Fns::withNamedLock(
         $lockName,
         Fns::identity(),
         function ( $x ) use ( &$addOne ) { return $addOne( $x + 1 ); }
     );

     $this->assertEquals( 13, $addOne( 12 ), 'Should not recurse' );

     $addTwo = Fns::withNamedLock(
         $lockName,
         Fns::identity(),
         function ( $x ) use ( $addOne ) { return pipe( $addOne, $addOne) ( $x ); }
     );

     $this->assertEquals( 10, $addTwo( 10 ), 'Should return 10 because $addOne is locked by the same name as $addTwo' );
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$name` | **mixed** |  |
| `$...$returnFn` | **mixed** |  |
| `$...$fn` | **mixed** |  |




---

### withoutRecursion



```php
Fns::withoutRecursion( mixed $...$returnFn, mixed $...$fn ): callable|mixed
```

- Curried :: ( *… → String ) → ( *… → a ) → ( *… → a )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$returnFn` | **mixed** |  |
| `$...$fn` | **mixed** |  |




---

### liftA2



```php
Fns::liftA2( mixed $...$fn, mixed $...$monadA, mixed $...$monadB ): callable|mixed
```

- Curried :: ( a → b → c ) → m a → m b → m c

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$monadA` | **mixed** |  |
| `$...$monadB` | **mixed** |  |




---

### liftA3



```php
Fns::liftA3( mixed $...$fn, mixed $...$monadA, mixed $...$monadB, mixed $...$monadC ): callable|mixed
```

- Curried :: ( a → b → c → d ) → m a → m b → m c → m d

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$monadA` | **mixed** |  |
| `$...$monadB` | **mixed** |  |
| `$...$monadC` | **mixed** |  |




---

### liftN



```php
Fns::liftN( mixed $...$n, mixed $...$fn, mixed $...$monad ): callable|mixed
```

- Curried :: Number->( ( * ) → a ) → ( *m ) → m a

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$n` | **mixed** |  |
| `$...$fn` | **mixed** |  |
| `$...$monad` | **mixed** |  |




---

### until



```php
Fns::until( mixed $...$predicate, mixed $...$fns ): callable|mixed
```

- Curried :: ( b → bool ) → [( a → b )] → a → b

Executes consecutive functions until their $predicate($fn(...$args)) is true. When a result fulfils predicate then it is returned.

```
      $fns = [
        $add(1),
        $add(5),
        $add(10),
        $add(23),
     ];

     $this->assertSame( 20, Fns::until( Relation::gt( Fns::__, 18 ), $fns )( 10 ) );
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$fns` | **mixed** |  |




---

### init



```php
Fns::init(  )
```



* This method is **static**.



---

### noop



```php
Fns::noop(  )
```



* This method is **static**.



---

### maybeToEither

Curried function that transforms a Maybe into an Either.

```php
Fns::maybeToEither( mixed|null $or = null, \WPML\FP\Maybe|null $maybe = null ): callable|\WPML\FP\Either
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$or` | **mixed&#124;null** |  |
| `$maybe` | **\WPML\FP\Maybe&#124;null** |  |




---

## Logic





* Full name: \WPML\FP\Logic


### not



```php
Logic::not( mixed $mixed ): callable|boolean
```

- Curried :: mixed->bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mixed` | **mixed** |  |




---

### isNotNull



```php
Logic::isNotNull( mixed $mixed ): callable|boolean
```

- Curried :: mixed->bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mixed` | **mixed** |  |




---

### ifElse



```php
Logic::ifElse( mixed $...$predicate, mixed $...$first, mixed $...$second ): callable
```

- Curried :: ( a->bool )->callable->callable->callable

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$first` | **mixed** |  |
| `$...$second` | **mixed** |  |




---

### when



```php
Logic::when( mixed $...$predicate, mixed $...$fn ): callable
```

- Curried :: ( a->bool )->callable->callable

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$fn` | **mixed** |  |




---

### unless



```php
Logic::unless( mixed $...$predicate, mixed $...$fn ): callable
```

- Curried :: ( a->bool )->callable->callable

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$fn` | **mixed** |  |




---

### cond



```php
Logic::cond( mixed $...$conditions, mixed $...$fn ): callable
```

- Curried :: [( a->bool ), callable]->callable

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$conditions` | **mixed** |  |
| `$...$fn` | **mixed** |  |




---

### both



```php
Logic::both( mixed $...$a, mixed $...$b, mixed $...$data ): callable
```

- Curried :: ( a → bool ) → ( a → bool ) → a → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |
| `$...$data` | **mixed** |  |




---

### allPass



```php
Logic::allPass( array $predicates ): callable
```

- Curried :: [( *… → bool )] → ( *… → bool )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$predicates` | **array** |  |




---

### anyPass



```php
Logic::anyPass( array $predicates ): callable
```

- Curried :: [( *… → bool )] → ( *… → bool )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$predicates` | **array** |  |




---

### complement



```php
Logic::complement( mixed $...$fn ): callable
```

- Curried :: ( *… → * ) → ( *… → bool )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |




---

### defaultTo



```php
Logic::defaultTo( mixed $...$a, mixed $...$b ): callable|mixed
```

- Curried :: a → b → a | b

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### either



```php
Logic::either( mixed $...$a, mixed $...$b ): callable|boolean
```

- Curried :: ( *… → bool ) → ( *… → bool ) → ( *… → bool )

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### 



```php
Logic::(  ): 
```

static callable|mixed until ( ...$predicate, ...$transform, ...$data ) - Curried :: ( a → bool ) → ( a → a ) → a → a





---

### propSatisfies



```php
Logic::propSatisfies( mixed $...$predicate, mixed $...$prop, mixed $...$data ): callable|boolean
```

- Curried :: ( a → bool ) → String → [String => a] → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$prop` | **mixed** |  |
| `$...$data` | **mixed** |  |




---

### 



```php
Logic::(  ): 
```

static callable|bool isArray ( ...$a ) - Curried :: a → bool





---

### isEmpty



```php
Logic::isEmpty( mixed $...$a ): callable|boolean
```

- Curried:: a → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |




---

### init



```php
Logic::init(  )
```



* This method is **static**.



---

## Lst

Lst class contains functions for working on ordered arrays indexed with numerical keys



* Full name: \WPML\FP\Lst


### append



```php
Lst::append( mixed $mixed, mixed $array ): callable|array
```

- Curried :: mixed->array->array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mixed` | **mixed** |  |
| `$array` | **mixed** |  |




---

### fromPairs



```php
Lst::fromPairs( mixed $array ): callable|array
```

- Curried :: [[a, b]] → [a => b]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$array` | **mixed** |  |




---

### toObj



```php
Lst::toObj( mixed $array ): callable|array
```

- Curried :: array → object

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$array` | **mixed** |  |




---

### pluck



```php
Lst::pluck( mixed $...$prop, mixed $...$array ): callable|array
```

- Curried :: string → array → array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$prop` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### partition



```php
Lst::partition( mixed $...$predicate, mixed $...$target ): callable|array
```

- Curried :: ( a → bool ) → [a] → [[a], [a]]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### sort



```php
Lst::sort( mixed $...$fn, mixed $...$target ): callable|array
```

- Curried :: ( ( a, a ) → int ) → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### unfold



```php
Lst::unfold( mixed $...$fn, mixed $...$seed ): callable|array
```

- Curried :: ( a → [b] ) → * → [b]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$fn` | **mixed** |  |
| `$...$seed` | **mixed** |  |




---

### zip



```php
Lst::zip( mixed $...$a, mixed $...$b ): callable|array
```

- Curried :: [a] → [b] → [[a, b]]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### zipObj



```php
Lst::zipObj( mixed $...$a, mixed $...$b ): callable|array
```

- Curried :: [a] → [b] → [a => b]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### zipWith



```php
Lst::zipWith( mixed $...$f, mixed $...$a, mixed $...$b ): callable|array
```

- Curried :: ( ( a, b ) → c ) → [a] → [b] → [c]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$f` | **mixed** |  |
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### join



```php
Lst::join( mixed $...$glue, mixed $...$array ): callable|string
```

- Curried :: string → [a] → string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$glue` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### concat



```php
Lst::concat( mixed $...$a, mixed $...$b ): callable|array
```

- Curried :: [a] → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### find



```php
Lst::find( mixed $...$predicate, mixed $...$array ): callable|array|null
```

- Curried :: ( a → bool ) → [a] → a | null

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### flattenToDepth



```php
Lst::flattenToDepth( mixed $...$depth, mixed $...$array ): callable|array
```

- Curried :: int → [[a]] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$depth` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### flatten



```php
Lst::flatten( mixed $...$array ): callable|array
```

- Curried :: [[a]] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$array` | **mixed** |  |




---

### includes



```php
Lst::includes( mixed $...$val, mixed $...$array ): callable|boolean
```

- Curried :: a → [a] → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$val` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### nth



```php
Lst::nth( mixed $...$n, mixed $...$array ): callable|boolean
```

- Curried :: int → [a] → a | null

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$n` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### first


```php
Lst::first(mixed $...$array): callable|boolean
```


- Curried :: [a, b] -> a | null

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$array` | **mixed** |  Array of elements to get the first of them. |

---

### last



```php
Lst::last( mixed $...$array ): callable|boolean
```

- Curried :: [a, b] → b | null

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$array` | **mixed** |  Array of elements to get the last of them. |




---

### length



```php
Lst::length( mixed $...$array ): callable|integer
```

- Curried :: [a] → int

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$array` | **mixed** |  |




---

### take



```php
Lst::take( mixed $...$n, mixed $...$array ): callable|array
```

- Curried :: int → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$n` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### takeLast



```php
Lst::takeLast( mixed $...$n, mixed $...$array ): callable|array
```

- Curried :: int → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$n` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### slice



```php
Lst::slice( mixed $...$offset, mixed $...$limit, mixed $...$array ): callable|array
```

- Curried :: int → int->[a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$offset` | **mixed** |  |
| `$...$limit` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### drop



```php
Lst::drop( mixed $...$n, mixed $...$array ): callable|array
```

- Curried :: int → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$n` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### dropLast



```php
Lst::dropLast( mixed $...$n, mixed $...$array ): callable|array
```

- Curried :: int → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$n` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### makePair



```php
Lst::makePair( mixed $...$a, mixed $...$b ): callable|array
```

- Curried :: mixed → mixed → array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### 



```php
Lst::(  ): 
```

static callable|array make ( ...$a ) - Curried :: mixed → array





---

### insert



```php
Lst::insert( mixed $...$index, mixed $...$v, mixed $...$array ): callable|array
```

- Curried :: int → mixed → array → array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$index` | **mixed** |  |
| `$...$v` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### range



```php
Lst::range( mixed $...$from, mixed $...$to ): callable|array
```

- Curried :: int → int → array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$from` | **mixed** |  |
| `$...$to` | **mixed** |  |




---

### xprod



```php
Lst::xprod( mixed $...$a, mixed $...$b ): callable|array
```

- Curried :: [a]->[b]->[a, b]

Creates a new list out of the two supplied by creating each possible pair from the lists.

```
$a              = [ 1, 2, 3 ];
$b              = [ 'a', 'b', 'c' ];
$expectedResult = [
  [ 1, 'a' ], [ 1, 'b' ], [ 1, 'c' ],
  [ 2, 'a' ], [ 2, 'b' ], [ 2, 'c' ],
  [ 3, 'a' ], [ 3, 'b' ], [ 3, 'c' ],
];

$this->assertEquals( $expectedResult, Lst::xprod( $a, $b ) );
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### prepend



```php
Lst::prepend( mixed $...$val, mixed $...$array ): callable|array
```

- Curried:: a → [a] → [a]

Returns a new array with the given element at the front, followed by the contents of the list.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$val` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### reverse



```php
Lst::reverse( mixed $...$array ): callable|array
```

- Curried:: [a] → [a]

Returns a new array with the elements reversed.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$array` | **mixed** |  |




---

### init



```php
Lst::init(  )
```



* This method is **static**.



---

### keyBy

Curried function that keys the array by the given key

```php
Lst::keyBy( string $key = null, array $array = null ): array|callable
```

keyBy :: string -> array -> array

```
$data = [
   [ 'x' => 'a', 'y' => 123 ],
   [ 'x' => 'b', 'y' => 456 ],
];

Lst::keyBy( 'x', $data );
[
   'a' => [ 'x' => 'a', 'y' => 123 ],
   'b' => [ 'x' => 'b', 'y' => 456 ],
],
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **string** |  |
| `$array` | **array** |  |




---

## Math





* Full name: \WPML\FP\Math


### multiply



```php
Math::multiply( mixed $...$a, mixed $...$b ): callable|mixed
```

- Curried :: Number → Number → Number

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### add



```php
Math::add( mixed $...$a, mixed $...$b ): callable|mixed
```

- Curried :: Number → Number → Number

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### product



```php
Math::product( mixed $...$array ): callable|mixed
```

- Curried :: [Number] → Number

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$array` | **mixed** |  |




---

### init



```php
Math::init(  )
```



* This method is **static**.



---

## Obj





* Full name: \WPML\FP\Obj


### prop



```php
Obj::prop( mixed $...$key, mixed $...$obj ): static
```

- Curried :: string->Collection|array|object->mixed|null


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$key` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### propOr



```php
Obj::propOr( mixed $...$default, mixed $...$key, mixed $...$obj ): static
```

- Curried :: mixed->string->Collection|array|object->mixed|null


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$default` | **mixed** |  |
| `$...$key` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### props



```php
Obj::props( mixed $...$keys, mixed $...$obj ): callable|array
```

- Curried :: [keys] → Collection|array|object → [v]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$keys` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### path



```php
Obj::path( mixed $...$path, mixed $...$obj ): static
```

- Curried :: array->Collection|array|object->mixed|null


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$path` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### pathOr



```php
Obj::pathOr( mixed $...$default, mixed $...$path, mixed $...$obj ): callable|mixed
```

- Curried :: mixed → array → Collection|array|object → mixed

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$default` | **mixed** |  |
| `$...$path` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### assoc



```php
Obj::assoc( mixed $...$key, mixed $...$value, mixed $...$item ): static
```

- Curried :: string->mixed->Collection|array|object->mixed|null


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$key` | **mixed** |  |
| `$...$value` | **mixed** |  |
| `$...$item` | **mixed** |  |




---

### assocPath



```php
Obj::assocPath( mixed $...$path, mixed $...$value, mixed $...$item ): static
```

- Curried :: array->mixed->Collection|array|object->mixed|null


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$path` | **mixed** |  |
| `$...$value` | **mixed** |  |
| `$...$item` | **mixed** |  |




---

### lens



```php
Obj::lens( mixed $...$getter, mixed $...$setter ): static
```

- Curried :: callable->callable->callable


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$getter` | **mixed** |  |
| `$...$setter` | **mixed** |  |




---

### lensProp



```php
Obj::lensProp( mixed $...$prop ): static
```

- Curried :: string->callable


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$prop` | **mixed** |  |




---

### lensPath



```php
Obj::lensPath( mixed $...$path ): static
```

- Curried :: array->callable


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$path` | **mixed** |  |




---

### view



```php
Obj::view( mixed $...$lens, mixed $...$obj ): static
```

- Curried :: callable->Collection|array|object->mixed


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$lens` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### set



```php
Obj::set( mixed $...$lens, mixed $...$value, mixed $...$obj ): static
```

- Curried :: callable->mixed->Collection|array|object->mixed


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$lens` | **mixed** |  |
| `$...$value` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### over



```php
Obj::over( mixed $...$lens, mixed $...$transformation, mixed $...$obj ): static
```

- Curried :: callable->callable->Collection|array|object->mixed


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$lens` | **mixed** |  |
| `$...$transformation` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### pick



```php
Obj::pick( mixed $...$props, mixed $...$obj ): static
```

- Curried :: array->Collection|array->Collection|array


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$props` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### pickAll



```php
Obj::pickAll( mixed $...$props, mixed $...$obj ): static
```

- Curried :: array->Collection|array->Collection|array


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$props` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### pickBy



```php
Obj::pickBy( mixed $...$predicate, mixed $...$obj ): static
```

- Curried :: ( ( v, k ) → bool ) → Collection|array->Collection|array


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$predicate` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### project



```php
Obj::project( mixed $...$props, mixed $...$target ): static
```

- Curried :: array->Collection|array->Collection|array


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$props` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### where



```php
Obj::where( array $condition ): static
```

- Curried :: [string → ( * → bool )] → bool


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$condition` | **array** |  |




---

### has



```php
Obj::has( mixed $...$prop, mixed $...$item ): callable|boolean
```

- Curried :: string → a → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$prop` | **mixed** |  |
| `$...$item` | **mixed** |  |




---

### evolve



```php
Obj::evolve( mixed $...$transformations, mixed $...$item ): callable|mixed
```

- Curried :: array → array → array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$transformations` | **mixed** |  |
| `$...$item` | **mixed** |  |




---

### objOf



```php
Obj::objOf( mixed $...$key, mixed $...$value ): callable|array
```

- Curried :: string -> mixed -> array

Creates an object containing a single key:value pair.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$key` | **mixed** |  |
| `$...$value` | **mixed** |  |




---

### keys



```php
Obj::keys( mixed $...$obj ): callable|array
```

- Curried :: object|array->array

Returns
 - keys if argument is an array
 - public properties' names if argument is an object
 - keys if argument is Collection

```
$this->assertEquals( [ 0, 1, 2 ], Obj::keys( [ 'a', 'b', 'c' ] ) );
$this->assertEquals( [ 'a', 'b', 'c' ], Obj::keys( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );

$this->assertEquals( [ 0, 1, 2 ], Obj::keys( \wpml_collect( [ 'a', 'b', 'c' ] ) ) );
$this->assertEquals( [ 'a', 'b', 'c' ], Obj::keys( \wpml_collect( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) ) );

$this->assertEquals( [ 'a', 'b', 'c' ], Obj::keys( (object) [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$obj` | **mixed** |  |




---

### values



```php
Obj::values( mixed $...$obj ): callable|array
```

- Curried :: object|array->array

Returns
 - values if argument is an array
 - public properties' values if argument is an object
 - values if argument is Collection

```
$this->assertEquals( [ 'a', 'b', 'c' ], Obj::values( [ 'a', 'b', 'c' ] ) );
$this->assertEquals( [ 1, 2, 3 ], Obj::values( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );

$this->assertEquals( [ 'a', 'b', 'c' ], Obj::values( \wpml_collect( [ 'a', 'b', 'c' ] ) ) );
$this->assertEquals( [ 1, 2, 3 ], Obj::values( \wpml_collect( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) ) );

$this->assertEquals( [ 1, 2, 3 ], Obj::values( (object) [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$obj` | **mixed** |  |




---

### replaceRecursive



```php
Obj::replaceRecursive( mixed $array, mixed $...$target ): callable|array
```

- Curried :: array->array->array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$array` | **mixed** |  |
| `$...$target` | **mixed** |  |




---

### init



```php
Obj::init(  )
```



* This method is **static**.



---

## Relation





* Full name: \WPML\FP\Relation


### equals



```php
Relation::equals( mixed $...$a, mixed $...$b ): callable|boolean
```

- Curried :: a->b->bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### lt



```php
Relation::lt( mixed $...$a, mixed $...$b ): callable|boolean
```

- Curried :: a->b->bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### lte



```php
Relation::lte( mixed $...$a, mixed $...$b ): callable|boolean
```

- Curried :: a->b->bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### gt



```php
Relation::gt( mixed $...$a, mixed $...$b ): callable|boolean
```

- Curried :: a->b->bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### gte



```php
Relation::gte( mixed $...$a, mixed $...$b ): callable|boolean
```

- Curried :: a->b->bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### propEq



```php
Relation::propEq( mixed $...$prop, mixed $...$value, mixed $...$obj ): callable|boolean
```

- Curried :: String → a → array → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$prop` | **mixed** |  |
| `$...$value` | **mixed** |  |
| `$...$obj` | **mixed** |  |




---

### sortWith



```php
Relation::sortWith( mixed $...$comparators, mixed $...$array ): callable|array
```

- Curried :: [(a, a) → int] → [a] → [a]

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$comparators` | **mixed** |  |
| `$...$array` | **mixed** |  |




---

### init



```php
Relation::init(  )
```



* This method is **static**.



---

## Str





* Full name: \WPML\FP\Str


### tail



```php
Str::tail( mixed $string ): string
```

- Curried :: string->string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$string` | **mixed** |  |




---

### split



```php
Str::split( mixed $...$delimiter, mixed $...$str ): array
```

- Curried :: string->string->string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$delimiter` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### includes



```php
Str::includes( mixed $...$needle, mixed $...$str ): callable|boolean
```

- Curried :: string → string → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$needle` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### trim



```php
Str::trim( mixed $...$trim, mixed $...$str ): callable|string
```

- Curried :: string → string → string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$trim` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### concat



```php
Str::concat( mixed $...$a, mixed $...$b ): callable|string
```

- Curried :: string → string → string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$a` | **mixed** |  |
| `$...$b` | **mixed** |  |




---

### sub



```php
Str::sub( mixed $...$start, mixed $...$str ): callable|string
```

- Curried :: int → string → string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$start` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### startsWith



```php
Str::startsWith( mixed $...$test, mixed $...$str ): callable|string
```

- Curried :: string → string → bool

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$test` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### pos



```php
Str::pos( mixed $...$test, mixed $...$str ): callable|string
```

- Curried :: string → string → int

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$test` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### len



```php
Str::len( mixed $...$str ): callable|string
```

- Curried :: string → int

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$str` | **mixed** |  |




---

### replace



```php
Str::replace( mixed $...$find, mixed $...$replace, mixed $...$str ): callable|string
```

- Curried :: string → string → string → string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$find` | **mixed** |  |
| `$...$replace` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### pregReplace



```php
Str::pregReplace( mixed $...$pattern, mixed $...$replace, mixed $...$str ): callable|string
```

- Curried :: string → string → string → string

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$pattern` | **mixed** |  |
| `$...$replace` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### match



```php
Str::match( mixed $...$pattern, mixed $...$str ): callable|string
```

- Curried :: string → string → array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$pattern` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### matchAll



```php
Str::matchAll( mixed $...$pattern, mixed $...$str ): callable|string
```

- Curried :: string → string → array

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$pattern` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### wrap



```php
Str::wrap( mixed $...$before, mixed $...$after, mixed $...$str ): callable|string
```

- Curried :: string → string → string

Wraps a string inside 2 other strings

```
$wrapsInDiv = Str::wrap( '<div>', '</div>' );
$wrapsInDiv( 'To be wrapped' ); // '<div>To be wrapped</div>'
```

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$...$before` | **mixed** |  |
| `$...$after` | **mixed** |  |
| `$...$str` | **mixed** |  |




---

### init



```php
Str::init(  )
```



* This method is **static**.



---



--------
> This document was automatically generated from source code comments on 2020-09-19 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
