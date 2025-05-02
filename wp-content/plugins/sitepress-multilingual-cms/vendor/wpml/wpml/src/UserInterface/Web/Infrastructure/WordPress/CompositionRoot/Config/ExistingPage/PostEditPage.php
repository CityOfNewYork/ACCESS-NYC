<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ExistingPage;

use WPML\UserInterface\Web\Core\SharedKernel\Config\ExistingPageInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Notice;

class PostEditPage implements ExistingPageInterface {


  public function isActive() {
    return isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'post.php';
  }


  public function renderNotice( Notice $notice ) {
    ob_start();
    $notice->render();
    $noticeHtml = ob_get_clean();

    echo <<<HTML
<div class="wpml-notice-container wpml-notice-container-edit-php" style="visibility: hidden;">
{$noticeHtml}
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
   var notice = document.querySelector('.wpml-notice-container-edit-php');
   
   if(!notice) return
   
   function insertNoticeInBlockEditor(){
       var innerNotice = notice.firstElementChild
       var afterEditorHeader = document.querySelector('.edit-post-header');
       
       if(afterEditorHeader) {
           afterEditorHeader.parentNode.insertAdjacentElement('beforeend',notice);
           notice.style.visibility = 'visible';
           notice.style.display = 'flex';
           notice.style.justifyContent = 'center';
           notice.style.marginBottom = '5px';
           innerNotice.style.width = '95%'
       } else {
           setTimeout(insertNoticeInBlockEditor, 500);
       }
   }
   
   function insertNoticeInClassicEditor(){
       var innerNotice = notice.firstElementChild
       var afterTitle = document.querySelector('.wp-header-end');
       
       if(afterTitle) {
           afterTitle.parentNode.insertBefore(notice, afterTitle.nextSibling);
           notice.style.visibility = 'visible';
           notice.style.display = 'flex';
           notice.style.justifyContent = 'center';
           innerNotice.style.width = '95%'
       } else {
           setTimeout(insertNoticeInClassicEditor, 500);
       }
   }
   
   if(document.body.classList.contains('block-editor-page')){
    insertNoticeInBlockEditor();   
   } else {
       insertNoticeInClassicEditor();
   }
});
</script>
HTML;

  }


}
