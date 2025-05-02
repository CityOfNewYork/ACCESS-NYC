<?php
namespace WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook;

use WPML\Infrastructure\WordPress\Port\Hook\PublicationStatusFilter;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook\DashboardPublicationStatusFilterInterface;

class DashboardPublicationStatusFilter
  extends PublicationStatusFilter
  implements DashboardPublicationStatusFilterInterface {
  const NAME = 'wpml_tm_dashboard_post_statuses';
}
