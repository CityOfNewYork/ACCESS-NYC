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
 * Contains Translation_Entry class
 *
 * @version $Id: entry.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage entry
 */

if ( ! class_exists( 'wfTranslation_Entry', false ) ) :
	/**
	 * Translation_Entry class encapsulates a translatable string
	 */
	class wfTranslation_Entry {

		/**
		 * Whether the entry contains a string and its plural form, default is false
		 *
		 * @var boolean
		 */
		var $is_plural = false;

		var $context             = null;
		var $singular            = null;
		var $plural              = null;
		var $translations        = array();
		var $translator_comments = '';
		var $extracted_comments  = '';
		var $references          = array();
		var $flags               = array();

		/**
		 * @param array $args associative array, support following keys:
		 *  - singular (string) -- the string to translate, if omitted and empty entry will be created
		 *  - plural (string) -- the plural form of the string, setting this will set {@link $is_plural} to true
		 *  - translations (array) -- translations of the string and possibly -- its plural forms
		 *  - context (string) -- a string differentiating two equal strings used in different contexts
		 *  - translator_comments (string) -- comments left by translators
		 *  - extracted_comments (string) -- comments left by developers
		 *  - references (array) -- places in the code this strings is used, in relative_to_root_path/file.php:linenum form
		 *  - flags (array) -- flags like php-format
		 */
		function __construct( $args = array() ) {
			// If no singular -- empty object.
			if ( ! isset( $args['singular'] ) ) {
				return;
			}
			// Get member variable values from args hash.
			foreach ( $args as $varname => $value ) {
				$this->$varname = $value;
			}
			if ( isset( $args['plural'] ) && $args['plural'] ) {
				$this->is_plural = true;
			}
			if ( ! is_array( $this->translations ) ) {
				$this->translations = array();
			}
			if ( ! is_array( $this->references ) ) {
				$this->references = array();
			}
			if ( ! is_array( $this->flags ) ) {
				$this->flags = array();
			}
		}

		/**
		 * Generates a unique key for this entry
		 *
		 * @return string|bool the key or false if the entry is empty
		 */
		function key() {
			if ( null === $this->singular || '' === $this->singular ) {
				return false;
			}

			// Prepend context and EOT, like in MO files.
			$key = ! $this->context ? $this->singular : $this->context . "\4" . $this->singular;
			// Standardize on \n line endings.
			$key = str_replace( array( "\r\n", "\r" ), "\n", $key );

			return $key;
		}

		/**
		 * @param object $other
		 */
		function merge_with( &$other ) {
			$this->flags      = array_unique( array_merge( $this->flags, $other->flags ) );
			$this->references = array_unique( array_merge( $this->references, $other->references ) );
			if ( $this->extracted_comments != $other->extracted_comments ) {
				$this->extracted_comments .= $other->extracted_comments;
			}

		}
	}
endif;
