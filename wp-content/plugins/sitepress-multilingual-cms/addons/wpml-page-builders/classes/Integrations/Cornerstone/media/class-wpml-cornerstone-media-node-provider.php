<?php

class WPML_Cornerstone_Media_Node_Provider {

	private $media_translate;

	private $nodes = array();

	public function __construct( WPML_Page_Builders_Media_Translate $media_translate ) {
		$this->media_translate = $media_translate;
	}

	/**
	 * @param string $type
	 *
	 * @return WPML_Cornerstone_Media_Node|null
	 */
	public function get( $type ) {
		if ( ! array_key_exists( $type, $this->nodes ) ) {
			$this->add( $type );
		}

		return $this->nodes[ $type ];
	}

	/**
	 * @param string $type
	 */
	private function add( $type ) {
		switch ( $type ) {
			case 'image':
				$node = new WPML_Cornerstone_Media_Node_Image( $this->media_translate );
				break;

			case 'classic:creative-cta':
				$node = new WPML_Cornerstone_Media_Node_Classic_Creative_CTA( $this->media_translate );
				break;

			case 'classic:feature-box':
				$node = new WPML_Cornerstone_Media_Node_Classic_Feature_Box( $this->media_translate );
				break;

			case 'classic:card':
				$node = new WPML_Cornerstone_Media_Node_Classic_Card( $this->media_translate );
				break;

			case 'classic:image':
				$node = new WPML_Cornerstone_Media_Node_Classic_Image( $this->media_translate );
				break;

			case 'classic:promo':
				$node = new WPML_Cornerstone_Media_Node_Classic_Promo( $this->media_translate );
				break;

			default:
				$node = null;
		}

		$this->nodes[ $type ] = $node;
	}
}
