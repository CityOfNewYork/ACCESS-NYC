<?php
/**
 * This is a modified version of the POMO library included with WordPress. The WordPress copyright has been included
 * for attribution.
 */

/*
WordPress - Web publishing software

Copyright 2011-2020 by the contributors

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

This program incorporates work covered by the following copyright and
permission notices:

  b2 is (c) 2001, 2002 Michel Valdrighi - https://cafelog.com

  Wherever third party code has been used, credit has been given in the code's
  comments.

  b2 is released under the GPL

and

  WordPress - Web publishing software

  Copyright 2003-2010 by the contributors

  WordPress is released under the GPL
 */


/**
 * Classes, which help reading streams of data from files.
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

if ( ! class_exists( 'wfPOMO_Reader', false ) ) :
	class wfPOMO_Reader {

		var $endian = 'little';
		var $_post  = '';

		private $is_overloaded;
		protected $_pos;

		/**
		 * PHP5 constructor.
		 */
		function __construct() {
			$this->is_overloaded = ( ( ini_get( 'mbstring.func_overload' ) & 2 ) != 0 ) && function_exists( 'mb_substr' ); // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
			$this->_pos          = 0;
		}

		/**
		 * Sets the endianness of the file.
		 *
		 * @param string $endian Set the endianness of the file. Accepts 'big', or 'little'.
		 */
		function setEndian( $endian ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			$this->endian = $endian;
		}

		/**
		 * Reads a 32bit Integer from the Stream
		 *
		 * @return mixed The integer, corresponding to the next 32 bits from
		 *  the stream of false if there are not enough bytes or on error
		 */
		function readint32() {
			$bytes = $this->read( 4 );
			if ( 4 != $this->strlen( $bytes ) ) {
				return false;
			}
			$endian_letter = ( 'big' === $this->endian ) ? 'N' : 'V';
			$int           = unpack( $endian_letter, $bytes );
			return reset( $int );
		}

		/**
		 * Reads an array of 32-bit Integers from the Stream
		 *
		 * @param integer $count How many elements should be read
		 * @return mixed Array of integers or false if there isn't
		 *  enough data or on error
		 */
		function readint32array( $count ) {
			$bytes = $this->read( 4 * $count );
			if ( 4 * $count != $this->strlen( $bytes ) ) {
				return false;
			}
			$endian_letter = ( 'big' === $this->endian ) ? 'N' : 'V';
			return unpack( $endian_letter . $count, $bytes );
		}

		/**
		 * @param string $string
		 * @param int    $start
		 * @param int    $length
		 * @return string
		 */
		function substr( $string, $start, $length ) {
			if ( $this->is_overloaded ) {
				return mb_substr( $string, $start, $length, 'ascii' );
			} else {
				return substr( $string, $start, $length );
			}
		}

		/**
		 * @param string $string
		 * @return int
		 */
		function strlen( $string ) {
			if ( $this->is_overloaded ) {
				return mb_strlen( $string, 'ascii' );
			} else {
				return strlen( $string );
			}
		}

		/**
		 * @param string $string
		 * @param int    $chunk_size
		 * @return array
		 */
		function str_split( $string, $chunk_size ) {
			if ( ! function_exists( 'str_split' ) ) {
				$length = $this->strlen( $string );
				$out    = array();
				for ( $i = 0; $i < $length; $i += $chunk_size ) {
					$out[] = $this->substr( $string, $i, $chunk_size );
				}
				return $out;
			} else {
				return str_split( $string, $chunk_size );
			}
		}

		/**
		 * @return int
		 */
		function pos() {
			return $this->_pos;
		}

		/**
		 * @return true
		 */
		function is_resource() {
			return true;
		}

		/**
		 * @return true
		 */
		function close() {
			return true;
		}
	}
endif;

if ( ! class_exists( 'wfPOMO_FileReader', false ) ) :
	class wfPOMO_FileReader extends wfPOMO_Reader {

		private $_f;

		/**
		 * @param string $filename
		 */
		function __construct( $filename ) {
			parent::__construct();
			$this->_f = fopen( $filename, 'rb' );
		}

		/**
		 * @param int $bytes
		 * @return string|false Returns read string, otherwise false.
		 */
		function read( $bytes ) {
			return fread( $this->_f, $bytes );
		}

		/**
		 * @param int $pos
		 * @return boolean
		 */
		function seekto( $pos ) {
			if ( -1 == fseek( $this->_f, $pos, SEEK_SET ) ) {
				return false;
			}
			$this->_pos = $pos;
			return true;
		}

		/**
		 * @return bool
		 */
		function is_resource() {
			return is_resource( $this->_f );
		}

		/**
		 * @return bool
		 */
		function feof() {
			return feof( $this->_f );
		}

		/**
		 * @return bool
		 */
		function close() {
			return fclose( $this->_f );
		}

		/**
		 * @return string
		 */
		function read_all() {
			$all = '';
			while ( ! $this->feof() ) {
				$all .= $this->read( 4096 );
			}
			return $all;
		}
	}
endif;

if ( ! class_exists( 'wfPOMO_StringReader', false ) ) :
	/**
	 * Provides file-like methods for manipulating a string instead
	 * of a physical file.
	 */
	class wfPOMO_StringReader extends wfPOMO_Reader {

		var $_str = '';

		/**
		 * PHP5 constructor.
		 */
		function __construct( $str = '' ) {
			parent::__construct();
			$this->_str = $str;
			$this->_pos = 0;
		}

		/**
		 * @param string $bytes
		 * @return string
		 */
		function read( $bytes ) {
			$data        = $this->substr( $this->_str, $this->_pos, $bytes );
			$this->_pos += $bytes;
			if ( $this->strlen( $this->_str ) < $this->_pos ) {
				$this->_pos = $this->strlen( $this->_str );
			}
			return $data;
		}

		/**
		 * @param int $pos
		 * @return int
		 */
		function seekto( $pos ) {
			$this->_pos = $pos;
			if ( $this->strlen( $this->_str ) < $this->_pos ) {
				$this->_pos = $this->strlen( $this->_str );
			}
			return $this->_pos;
		}

		/**
		 * @return int
		 */
		function length() {
			return $this->strlen( $this->_str );
		}

		/**
		 * @return string
		 */
		function read_all() {
			return $this->substr( $this->_str, $this->_pos, $this->strlen( $this->_str ) );
		}

	}
endif;

if ( ! class_exists( 'wfPOMO_CachedFileReader', false ) ) :
	/**
	 * Reads the contents of the file in the beginning.
	 */
	class wfPOMO_CachedFileReader extends wfPOMO_StringReader {
		/**
		 * PHP5 constructor.
		 */
		function __construct( $filename ) {
			parent::__construct();
			$this->_str = file_get_contents( $filename );
			if ( false === $this->_str ) {
				return false;
			}
			$this->_pos = 0;
		}
	}
endif;

if ( ! class_exists( 'wfPOMO_CachedIntFileReader', false ) ) :
	/**
	 * Reads the contents of the file in the beginning.
	 */
	class wfPOMO_CachedIntFileReader extends wfPOMO_CachedFileReader {
		/**
		 * PHP5 constructor.
		 */
		public function __construct( $filename ) {
			parent::__construct( $filename );
		}
	}
endif;
