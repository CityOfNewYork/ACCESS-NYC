<?php

namespace WPML\LanguageSwitcher;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;

class LsTemplateDomainUpdater {
	public function run( Collection $data, \wpdb $wpdb ) {
		$this->runUpdate();

		return Either::of( true );
	}

	public function runUpdate( $siteurl = null, $homepath = null ) {
		$data = get_option( \WPML_LS_Templates::OPTION_NAME );
		if ( ! $data ) {
			return;
		}

		if ( is_null( $siteurl ) ) {
			$siteurl = site_url();
		}
		if ( is_null( $homepath ) ) {
			$homepath = get_home_path();
		}

		$homepath = untrailingslashit( trim( $homepath ) );
		$siteurl  = untrailingslashit( trim( $siteurl ) );

		$propsWithUrls = [ 'css', 'js', 'base_uri', 'flags_base_uri' ];

		$getTemplateData = function ( $template ) {
			return method_exists( $template, 'get_template_data' ) ? $template->get_template_data() : $template;
		};

		$updatePath = function ( $templatePropValue ) use ( $homepath ) {
			return Fns::map(
				function ( $value ) use ( $homepath ) {
					return $this->setPath( $value, $homepath );
				},
				$templatePropValue
			);
		};

		$updateUrlOfSingleValue = function ( $templatePropValue ) use ( $siteurl ) {
			return $this->setUrl( $templatePropValue, $siteurl );
		};

		$updateUrl = function ( $templatePropValue ) use ( $updateUrlOfSingleValue ) {
			return is_array( $templatePropValue )
				? Fns::map( $updateUrlOfSingleValue, $templatePropValue )
				: $updateUrlOfSingleValue( $templatePropValue );
		};

		$transformations = [ 'path' => $updatePath ];
		foreach ( $propsWithUrls as $prop ) {
			$transformations[ $prop ] = $updateUrl;
		}

		$updateTemplateData = function ( $template ) use ( $getTemplateData, $transformations ) {
			$updatedTemplateData = Obj::evolve( $transformations, $getTemplateData( $template ) );

			if ( method_exists( $template, 'set_template_data' ) ) {
				$template->set_template_data( $updatedTemplateData );
			} else {
				$template = $updatedTemplateData;
			}

			return $template;
		};

		$data = Fns::map( $updateTemplateData, $data );
		update_option( \WPML_LS_Templates::OPTION_NAME, $data );

		return $data;
	}

	private function setPath( $path, $homepath ) {
		$pathParts    = explode( '/wp-content', $path );
		$origHomepath = $pathParts[0];

		return Str::replace( $origHomepath, $homepath, $path );
	}

	private function setUrl( $url, $siteurl ) {
		$url = trim( $url );

		return $this->maybeSetUrl(
			$this->maybeSetProtocol(
				$url,
				$siteurl
			),
			$siteurl
		);
	}

	private function maybeSetProtocol( $url, $siteurl ) {
		if ( Str::startsWith( 'http', $url ) ) {
			return $url;
		}

		$protocol = ( Str::startsWith( 'https', $siteurl ) ) ? 'https' : 'http';

		if ( ! Str::startsWith( ':', $url ) ) {
			$protocol .= ':';
		}

		if ( ! Str::startsWith( '//', $url ) ) {
			$protocol .= '//';
		}

		return $protocol . $url;
	}

	private function maybeSetUrl( $url, $siteurl ) {
		if ( Str::startsWith( $siteurl, $url ) ) {
			return $url;
		}

		$urlData   = wp_parse_url( $url );
		$urlDomain = $urlData['scheme'] . '://' . $urlData['host'];

		return Str::replace( $urlDomain, $siteurl, $url );
	}
}
