<?php

namespace WPML\PHP\Value;

use WPML\PHP\Exception\InvalidArgumentException;
use WPML\PHPUnit\TestCase;

class ValidateTest extends TestCase {


  /**
   * @dataProvider dataValidString
   */
  public function testValidString( $input ) {
    $this->assertEquals( $input, Validate::string( $input ) );
  }


  public function dataValidString() {
    return [
      'string' => [ 'test string' ],
      'empty'  => [ '' ],
    ];
  }


  /**
   * @dataProvider dataInvalidString
   */
  public function testInvalidString( $input ) {
    $this->expectException( InvalidArgumentException::class );
    Validate::string( $input );
  }


  public function dataInvalidString() {
    return [
      'integer' => [ 123 ],
      'float'   => [ 1.23 ],
      'boolean' => [ true ],
      'array'   => [ [] ],
      'object'  => [ new \stdClass() ],
      'null'    => [ null ],
    ];
  }


  public function testStringInArray() {
    // Available key.
    $array = [ 'title' => 'test string' ];
    $this->assertEquals( 'test string', Validate::string( [ $array, 'title' ] ) );

    // Not available key.
    $this->expectException( InvalidArgumentException::class );
    Validate::string( [ $array, 'key-does-not-exist' ] );
  }


  public function testValidateStringFallback() {
    $this->assertEquals( 123, Validate::string( null, 123 ) );
    $this->assertEquals( 'my-value', Validate::string( [ [], 'does-not-exist' ], 'my-value' ) );
  }


  /**
   * @dataProvider dataValidateArrayOfSameTypeValidData
   */
  public function testValidateArrayOfSameTypeValidData( $input, $callback, $expected = null ) {
    $expected = $expected ?? $input;
    $this->assertSame( $expected, Validate::arrayOfSameType( $input, $callback ) );
  }


  public function dataValidateArrayOfSameTypeValidData() {
    return [
      'empty array' => [ [], 'is_int' ],
      'array of integers' => [ [ 1, 2, 3 ], 'is_int' ],
      'array of 0' => [ [0], [ Validate::class, 'int' ] ],
      'array of integers' => [
        [ 0, 1, '2', 3 ],
        function( $v ) {
          return Validate::int( $v );
        },
        [ 0, 1, 2, 3 ] // Make sure the array is normalized.
      ],
      'array of strings' => [ [ 'a', 'b', 'c' ], 'is_string' ],
      'array of arrays' => [ [ [], [], [] ], 'is_array' ],
    ];
  }


  /**
   * @dataProvider dataValidateArrayOfSameTypeInvalidData
   */
  public function testValidateArrayOfSameTypeInvalidData( $input, $callback ) {
    $this->expectException( InvalidArgumentException::class );
    Validate::arrayOfSameType( $input, $callback );
  }


  public function dataValidateArrayOfSameTypeInvalidData() {
    return [
      'integer' => [ [ 1, 'a', 2 ], 'is_int' ],
      'float' => [ 1, 'is_int' ], // no array at all.
      'boolean' => [ [ true ], 'is_int' ],
      'array' => [ [ [] ], 'is_int' ],
      'object' => [ [ new \stdClass() ], 'is_int']
    ];
  }


  public function testValidateArray() {
    $array = [ 'a' => 1, 2 => 'b', 'c' => [] ];
    $this->assertEquals(
      $array,
      Validate::array(
        $array,
        [
          'a' => 'is_int',
          2 => 'is_string',
          '?c' => 'is_array', // Optional key which exists.
          '?d' => 'is_string' // Optional key which does not exist.
        ]
      )
    );
  }


  public function testValidateArrayFallback() {
    $this->assertEquals( [], Validate::arrayOfSameType( null, 'is_int', [] ) );
    $this->assertEquals(
      [ 1, 2, 3 ],
      Validate::array(
        [ 1, 2, 'a' ],
        [
          2 => 'is_int'
        ],
        [ 1, 2, 3 ]
      )
    );
  }

  /**
   * @dataProvider dataValidInt
   */
  public function testIsInt( $input ) {
    $this->assertSame( $input, Validate::int( $input ) );
  }


  public function dataValidInt() {
    return [
      'int' => [ 123 ],
      'zero' => [ 0 ],
    ];
  }


  /**
   * @dataProvider dataInvalidInt
   */
  public function testInvalidInt( $input ) {
    $this->expectException( InvalidArgumentException::class );
    Validate::int( $input );
  }


  public function dataInvalidInt() {
    return [
      'float' => [ 1.23 ],
      'boolean' => [ true ],
      'array' => [ [] ],
      'object' => [ new \stdClass() ],
      'null' => [ null ],
    ];
  }


  public function testValidateIntAllowsStringIntegers() {
    $validated = Validate::int( '123' );
    $this->assertEquals( 123, $validated );

    // Make sure it's an integer.
    $this->assertIsInt( $validated );
  }


  public function testIntInArray() {
    // Available key.
    $array = [ 'no' => 123 ];
    $this->assertEquals( 123, Validate::int( [ $array, 'no' ] ) );

    // Not available key.
    $this->expectException( InvalidArgumentException::class );
    Validate::int( [ $array, 'key-does-not-exist' ] );
  }


  public function testFallback() {
    $this->assertEquals( 123, Validate::int( null, 123 ) );
    $this->assertEquals( 'my-value', Validate::int( [ [], 'does-not-exist' ], 'my-value' ) );
  }


}
