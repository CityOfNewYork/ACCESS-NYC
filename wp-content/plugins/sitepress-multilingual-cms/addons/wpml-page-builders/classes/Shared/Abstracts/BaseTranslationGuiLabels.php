<?php

namespace WPML\Compatibility;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

abstract class BaseTranslationGuiLabels implements \IWPML_DIC_Action, \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_post_type_dto_filter' )
			->then( spreadArgs( [ $this, 'adjustObjectLabels' ] ) );
		Hooks::onFilter( 'wpml_translation_queue_post_types_filter' )
			->then( spreadArgs( [ $this, 'adjustObjectsLabels' ] ) );
		Hooks::onFilter( 'wpml_tm_job_list_post_types_filter' )
			->then( spreadArgs( [ $this, 'adjustObjectsLabels' ] ) );
	}

	/**
	 * @return string[]
	 */
	abstract protected function getPostTypes();

	/**
	 * @return string
	 */
	abstract protected function getFormat();

	/**
	 * @param string $label
	 * @param string $name
	 * @param bool   $isPlural
	 *
	 * @return string
	 */
	protected function formatLabel( $label, $name, $isPlural ) {
		return sprintf( $this->getFormat(), $label );
	}

	/**
	 * @param \WP_Post_Type $postTypeObject
	 *
	 * @return \WP_Post_Type
	 */
	public function adjustObjectLabels( $postTypeObject ) {
		$pts = $this->getPostTypes();
		if ( 'portfolio' === $postTypeObject->name ) {
			$foo = 'bar';
		}
		if ( ! in_array( $postTypeObject->name, $this->getPostTypes(), true ) ) {
			return $postTypeObject;
		}

		$postTypeObject->labels->name          = $this->formatLabel( $postTypeObject->labels->name, $postTypeObject->name, true );
		$postTypeObject->labels->singular_name = $this->formatLabel( $postTypeObject->labels->singular_name, $postTypeObject->name, false );

		return $postTypeObject;
	}

	/**
	 * @param \WP_Post_Type[] $postTypeObjects
	 *
	 * @return \WP_Post_Type[]
	 */
	public function adjustObjectsLabels( $postTypeObjects ) {
		foreach ( $postTypeObjects as &$postTypeObject ) {
			$postTypeObject = $this->adjustObjectLabels( $postTypeObject );
		}
		return $postTypeObjects;
	}

}
