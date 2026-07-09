<?php

namespace WPML\ST\StringsScanning\JS;

class Scanner {

	/**
	 * @param string                                             $file
	 * @param string                                             $componentDomain
	 * @param callable(string, string, string, string, int):void $storeResults
	 *
	 * @return void
	 */
	public function scan( $file, $componentDomain, $storeResults ) {
		try {
			require_once WPML_ST_PATH . '/lib/StringScanning/vendor/autoload.php';

			if (
				! class_exists( '\Gettext\Translations' )
				|| ! class_exists( '\Gettext\Translation' )
				|| ! class_exists( '\WP_CLI\I18n\JsFunctionsScanner' )
			) {
				return;
			}

			$xdebugMaxNestingLevel = (string) ini_get( 'xdebug.max_nesting_level' );
			/** @see https://github.com/mck89/peast?tab=readme-ov-file#known-issues */
			ini_set( 'xdebug.max_nesting_level', '1024' );

			$translations = [ new \Gettext\Translations() ];
			$options      = [
				'file'          => $file,
				'extensions'    => [ 'js', 'jsx' ],
				'addReferences' => true, // Optional: If enabled, we also get the file and the line where the string was found.
				'functions'     => [
					'__'  => 'text_domain',
					'_x'  => 'text_context_domain',
					'_n'  => 'single_plural_number_domain',
					'_nx' => 'single_plural_number_context_domain',
				],
			];

			$fileContent = file_get_contents( $file );
			$scanner     = new \WP_CLI\I18n\JsFunctionsScanner( $fileContent );
			$scanner->disableCommentsExtraction();
			$scanner->saveGettextFunctions( $translations, $options );

			list( $handle, $textdomain ) = ScriptRegistry::getHandleAndTextdomainByPath( $file );

			if ( ! $handle ) {
				return;
			}

			$textdomain = $textdomain ?? $componentDomain;
			$jedDomain  = \WPML_ST_JED_Domain::get( $textdomain, $handle );

			foreach ( $translations[0] as $translation ) {
				/** @var \Gettext\Translation $translation */
				$stringValue = $translation->getOriginal();
				$refs        = $translation->getReferences();
				$firstLine   = $refs[0][1] ?? null;
				$storeResults( $stringValue, $jedDomain, $translation->getContext(), $file, $firstLine );
			}

			ini_set( 'xdebug.max_nesting_level', $xdebugMaxNestingLevel );
		} catch ( \Throwable $e ) {
			error_log( 'Failure in JS string scanning: ' . $e->getMessage() );
		}
	}
}
