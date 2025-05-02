<?php

namespace WPML\Legacy\Component\Post\Application;

class TranslationEditorMode {


  /**
   *  @param array<int, int> $postIds
   *
   * @return array<int, int>
   */
  public function getBlockedPosts( array $postIds ): array {
    $blockedPostsRaw = \WPML_TM_Post_Edit_TM_Editor_Mode::get_blocked_posts( $postIds );
    if ( ! is_array( $blockedPostsRaw ) ) {
      return [];
    }

    return array_map( 'intval', $blockedPostsRaw );
  }


}
