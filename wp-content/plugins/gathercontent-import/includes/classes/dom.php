<?php
namespace GatherContent\Importer;
use DOMDocument;

class Dom extends DOMDocument {

	/**
	 * Initiate the DOMDocument object, ensuring UTF-8
	 *
	 * @see http://stackoverflow.com/a/8218649/1883421
	 *
	 * @since 3.0.0
	 *
	 * @param string $content HTML content
	 */
	public function __construct( $content ) {
		@$this->loadHTML( '<?xml encoding="UTF-8">' . $content );

		// Fixes data attributes like:
		// `data-gcatts="{&quot;align&quot;:&quot;right&quot;,&quot;linkto&quot;:&quot;attachment-page&quot;,&quot;size&quot;:&quot;full&quot;}"`
		// to correct:
		// data-gcatts='{"align":"right","linkto":"attachment-page","size":"full"}'
		$this->normalizeDocument();
	}

	/**
	 * Returns the normalized content.
	 *
	 * @since  3.0.0
	 *
	 * @return string  HTML content
	 */
	public function get_content() {
		$body = $this->saveHTML( $this->getElementsByTagName( 'body' )->item(0) );
		return str_replace( array( '<body>', '</body>' ), '', $body );
	}

}
