<?php
namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

abstract class AbstractStringItemApiController extends AbstractItemApiController {
	protected function getValidParametersForItems( array $extend = [] ) {
		return $this->getValidParameters(
			[
				'kind' => [
					'type'              => 'integer',
					'default'           => null,
					'validate_callback' => [ $this, 'validateKind' ],
				],
				'type' => [
					'type'              => 'integer',
					'default'           => null,
					'validate_callback' => [ $this, 'validateKind' ],
				],
				'source' => [
					'type'              => 'integer',
					'default'           => null,
					'validate_callback' => [ $this, 'validateSource' ],
				],
				'domain' => [
					'type'    => 'string',
					'default' => null,
				],
				'translationPriority' => [
					'type'              => 'string',
					'sanitize_callback' => [ 'WPML_REST_Arguments_Sanitation', 'string' ],
					'default'           => null,
				],
			]
		);
	}

	/**
	 * @param null|int $type
	 */
	public function validateType( $kind ): bool
	{
		return in_array(
			$kind,
			[
				StringItem::COMPONENT_TYPE_UNKNOWN,
				StringItem::COMPONENT_TYPE_PLUGIN,
				StringItem::COMPONENT_TYPE_THEME,
				StringItem::COMPONENT_TYPE_CORE,
			]
		);
	}

	/**
	 * @param null|int $kind
	 */
	public function validateKind( $kind ): bool
	{
		return in_array(
			$kind,
			[
				StringItem::STRING_TYPE_DEFAULT,
				StringItem::STRING_TYPE_AUTOREGISTER,
			]
		);
	}

	/**
	 * @param null|int $source
	 */
	public function validateSource( $source ): bool
	{
		return in_array(
			$source,
			[
				ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND,
				ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_BACKEND,
			]
		);
	}
}