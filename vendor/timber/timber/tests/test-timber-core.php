<?php

class TimberCoreTester extends TimberPost {

	public $public = 'public A';
	protected $protected = 'protected A';
	private $private = 'private A';
	public $existing = 'value from A';

	function foo() {
		return 'bar';
	}
}

class ClassB {

	public $public = 'public B';
	protected $protected = 'protected B';
	private $private = 'private B';
	public $existing = 'value from B';
}

class TestTimberCore extends Timber_UnitTestCase {

	function testCoreImport() {
		$post_id = $this->factory->post->create();
		$tc = new TimberCoreTester($post_id);
		$object = new stdClass();
		$object->frank = 'Drebin';
		$object->foo = 'Dark Helmet';
		$tc->import($object);
		$this->assertEquals('Drebin', $tc->frank);
		$this->assertEquals('bar', $tc->foo);
		$tc->import($object, true);
		$this->assertEquals('Dark Helmet', $tc->foo);
		$this->assertEquals('Drebin', $tc->frank);
	}

	function testCoreImportWithPropertyTypes() {
		$post_id = $this->factory->post->create();
		$tc = new TimberCoreTester($post_id);
		$object = new ClassB();
		$tc->import((object) (array) $object);
		$this->assertEquals('public B', $tc->public);
		$this->assertEquals('value from B', $tc->existing);
	}


}