<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ExistingPage;

use WPML\UserInterface\Web\Core\SharedKernel\Config\ExistingPageInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Notice;

class PostListingPage implements ExistingPageInterface {


  public function isActive() {
    return isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'edit.php';
  }


  public function renderNotice( Notice $notice ) {
    ob_start();
      $notice->render();
      $noticeHtml = ob_get_clean();

    echo <<<HTML
      <div class="wpml-notice-container wpml-notice-container-edit-php" style="display: none;">
        {$noticeHtml}
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          var notice = document.querySelector('.wpml-notice-container-edit-php');
          var afterTitle = document.querySelector('.wp-header-end');
          afterTitle.parentNode.insertBefore(notice, afterTitle.nextSibling);
          notice.style.display = 'block';
        } );
      </script>
HTML;
  }


}
