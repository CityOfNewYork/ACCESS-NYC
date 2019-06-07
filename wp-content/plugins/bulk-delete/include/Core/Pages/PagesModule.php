<?php

namespace BulkWP\BulkDelete\Core\Pages;

use BulkWP\BulkDelete\Core\Posts\PostsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Module for deleting pages.
 *
 * This class extends PostsModule since Page is a type of Post.
 *
 * @since 6.0.0
 */
abstract class PagesModule extends PostsModule {
	protected $item_type = 'pages';
}
