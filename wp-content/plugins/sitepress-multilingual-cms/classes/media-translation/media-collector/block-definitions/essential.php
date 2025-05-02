<?php

namespace WPML\MediaTranslation\MediaCollector;

return [
	new CollectorBlock(
		'essential-blocks/advanced-image',
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'image' ), new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'image' ), new PathResolverByString( 'url' ) ]
	),
	new CollectorBlock(
		'essential-blocks/slider',
		[ new PathResolverByString( 'imageId' ) ],
		[ new PathResolverByString( 'url' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'images' ) ]
	),
	new CollectorBlock(
		'essential-blocks/image-gallery',
		[ new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'url' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'sources' ) ]
	),
	new CollectorBlock(
		'essential-blocks/parallax-slider',
		[ new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'src' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'sliderData' ) ]
	),
];
