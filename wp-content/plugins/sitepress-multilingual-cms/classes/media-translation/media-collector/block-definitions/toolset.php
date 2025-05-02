<?php

namespace WPML\MediaTranslation\MediaCollector;

return [
	new CollectorBlock(
		'toolset-blocks/image',
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'url' ) ]
	),
	new CollectorBlock(
		'toolset-blocks/gallery',
		[ new PathResolverByRegex( '#data-id="(.*?)"#' ) ],
		[ new PathResolverByRegex( '#data-full-url="(.*?)"#' ) ],
		[ new PathResolverByString( 'innerHTML' ), new PathResolverByRegex( '#<img(.*?)>#' ) ]
	),
	new CollectorBlock(
		'toolset-blocks/image-slider',
		[ new PathResolverByRegex( '#data-id="(.*?)"#' ) ],
		[ new PathResolverByRegex( '#src="(.*?)"#' ) ],
		[ new PathResolverByString( 'innerHTML' ), new PathResolverByRegex( '#<img(.*?)>#' ) ]
	),
];
