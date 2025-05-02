<?php

namespace WPML\PHP\Value;

use WPML\PHP\Exception\InvalidArgumentException;
use WPML\PHPUnit\TestCase;

class InternalTest extends TestCase {


  public function testFallbackOrException() {
    $this->assertEquals( 'default', Internal::fallbackOrException( 'default', 'exception message' ) );

    $this->expectException( InvalidArgumentException::class );
    $this->expectExceptionMessage( 'exception message' );
    Internal::fallbackOrException( Internal::THROW_EXCEPTION, 'exception message' );
  }


  /**
   * @dataProvider dataGetValueFromArray
   */
  public function testGetValueFromArray( $input, $expected ) {
    $this->assertEquals( $expected, Internal::getValueFromArray( $input ) );
  }


  public function dataGetValueFromArray() {
    return [
      [ 'test string', 'test string' ],
      [ 123, 123 ],
      [ 1.23, 1.23 ],
      [ true, true ],
      [ null, null ],
      [ [], [] ],
      [ new \stdClass(), new \stdClass() ],
      [ [ 'title' => 'test string', 'subtitle' => 'test2' ], [ 'title' => 'test string', 'subtitle' => 'test2' ] ],
      [ [ [ 'title' => 'test string' ], 'title' ], 'test string' ],
      [ [ [ 'title' => 'test string' ], 'key-does-not-exist' ], Internal::KEY_DOES_NOT_EXIST ],
    ];
  }


  public function testMsgKeyDoesNotExist() {
    $this->assertEquals(
      "Value is not an array.",
      Internal::msgKeyDoesNotExist( 'a string' )
    );

    $this->assertEquals(
      "Key 'key-does-not-exist' does not exist in the array.",
      Internal::msgKeyDoesNotExist( [ [], 'key-does-not-exist' ] )
    );

    $this->assertEquals(
      "No key provided.",
      Internal::msgKeyDoesNotExist( [ [ 'array, but no key' ] ] )
    );

  }


}
