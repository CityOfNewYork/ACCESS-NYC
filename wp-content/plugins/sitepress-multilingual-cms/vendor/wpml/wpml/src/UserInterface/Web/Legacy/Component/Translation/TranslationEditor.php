<?php
namespace WPML\UserInterface\Web\Legacy\Component\Translation;

use WPML\PHP\Exception\RuntimeException;
use WPML\UserInterface\Web\Core\Component\Notices\WarningTranslationEdit\Application\TranslationEditorInterface;
use WP_Post;

use function WPML\Container\make;

class TranslationEditor implements TranslationEditorInterface {

  /**
   * @var ?\WPML_TM_Translation_Status_Display $_statusDisplay
   */
  private $_statusDisplay;

  /** @var ?\WPML_Translation_Element_Factory $_elementFactory */
  private $_elementFactory;


  /**
   *
   * @throws RuntimeException
   *
   * @return \WPML_TM_Translation_Status_Display
   */
  private function statusDisplay() {
    if ( $this->_statusDisplay === null ) {
      $wpml_tm_status_display_filter = $GLOBALS['wpml_tm_status_display_filter'] ?? null;
      if (
        ! $wpml_tm_status_display_filter
        && function_exists( 'wpml_tm_load_status_display_filter' )
      ) {
        wpml_tm_load_status_display_filter();
        $wpml_tm_status_display_filter = $GLOBALS['wpml_tm_status_display_filter'] ?? null;
      }

      if ( ! $wpml_tm_status_display_filter ) {
        throw new RuntimeException( 'WPML Translation Management is not loaded' );
      }

      $this->_statusDisplay = $wpml_tm_status_display_filter;
    }

    return $this->_statusDisplay;
  }


  /**
   * @return \WPML_Translation_Element_Factory
   */
  private function elementFactory() {
    if ( $this->_elementFactory === null ) {
      $this->_elementFactory = make( \WPML_Translation_Element_Factory::class );
    }

    return $this->_elementFactory;
  }


  public function getTranslationEditorLink( int $postId ): string {
    try {
      $post = get_post( $postId );
      if ( ! $post instanceof WP_Post ) {
        return '';
      }

      $post_element = $this->elementFactory()->create( $postId, 'post' );

      if (
        ! is_object( $post_element )
        || ! method_exists( $post_element, 'get_id' )
        || ! method_exists( $post_element, 'get_source_element' )
        || ! method_exists( $post_element, 'get_language_code' )
        || ! method_exists( $post_element, 'get_trid' )
      ) {
        return '';
      }

      $post_id             = $post_element->get_id();
      $source_post_element = $post_element->get_source_element();
      if ( $source_post_element ) {
        $post_id = $source_post_element->get_id();
      }

      $url = $this->statusDisplay()->filter_status_link(
        '',
        $post_id,
        $post_element->get_language_code() ?: '',
        $post_element->get_trid() ?: 0
      );

      if ( ! $url || ! is_string( $url ) ) {
        return '';
      }

      $url = remove_query_arg( 'return_url', $url );
      // make a full URL because can be used in frontend and admin
      $url = admin_url() . ltrim( $url, '/' );

      return $url;
    } catch ( \Throwable $e ) {
      // Just don't return the link if something goes wrong.
      return '';
    }
  }


}
