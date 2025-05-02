<?php

namespace WPML\MediaTranslation\MediaCollector;

class CollectorBlock {
	/** @var string */
	private $name;

	/** @var PathResolverInterface[] */
	private $path_to_id = [];

	/** @var PathResolverInterface[] */
	private $path_to_url = [];

	/** @var PathResolverInterface[] */
	private $path_to_multiple_media = [];

	/**
	 * MediaBlock constructor.
	 *
	 * @param string                  $name
	 * @param PathResolverInterface[] $pathToId
	 * @param PathResolverInterface[] $pathToUrl
	 * @param PathResolverInterface[] $pathToMultipleMedia
	 */
	public function __construct(
		$name,
		$pathToId = [],
		$pathToUrl = [],
		$pathToMultipleMedia = []
	) {
		$this->name                   = $name;
		$this->path_to_id             = $pathToId;
		$this->path_to_url            = $pathToUrl;
		$this->path_to_multiple_media = $pathToMultipleMedia;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $block
	 * @param array $mediaCollection
	 */
	public function collectIdsAndUrls( $block, &$mediaCollection ) {
		$mediaData = $this->getMultipleMediaArray( $block );

		foreach ( $mediaData as $media ) {
			$id = $this->getId( $media );
			if ( ! $id ) {
				continue;
			}

			$url = $this->getUrl( $media );
			if ( ! $url ) {
				continue;
			}

			if ( isset( $mediaCollection[ $url ] ) ) {
				continue;
			}

			$guid = get_the_guid( (int) $id );

			if ( $guid === $url ) {
				$mediaCollection[ $url ] = $id;
			}
		}

	}

	/**
	 * @param mixed $data
	 *
	 * @return int|null
	 */
	private function getId( $data ) {
		$id = $this->getByPath( $data, $this->path_to_id );
		return is_numeric( $id ) ? (int) $id : null;
	}

	/**
	 * @param mixed $data
	 *
	 * @return string|null
	 */
	private function getUrl( $data ) {
		$url = $this->getByPath( $data, $this->path_to_url );
		return is_string( $url ) ? $url : null;
	}

	/**
	 * @param mixed $data
	 * @param PathResolverInterface[] $path
	 *
	 * @return mixed
	 */
	private function getByPath( $data, $path ) {
		$keys     = array_keys( $path );
		$last_key = end( $keys );

		foreach ( $path as $i => $part ) {
			if ( $i === $last_key ) {
				return $part->getValue( $data );
			}
			$data = $part->resolvePath( $data );
		}

		return null;
	}

	private function getMultipleMediaArray( $block ) {
		$data = (array) $block;
		if ( ! $this->path_to_multiple_media ) {
			return [ $data ];
		}

		foreach ( $this->path_to_multiple_media as $part ) {
			$data = $part->resolvePath( $data );
		}

		return $data;
	}
}

