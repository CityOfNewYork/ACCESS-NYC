<?php

namespace NYCO;

use WP_Query;

class GcTemplatesSync {
  /**
   * Main key for actions and prefix for other identifiers.
   *
   * @var String
   */
  const ACTION = 'gc_templates_json_sync';

  /**
   * Main key for actions and prefix for other identifiers.
   *
   * @var String
   */
  const ACTION_IMPORT = 'gc_templates_json_import';

  /**
   * The post type name for the template mappings.
   *
   * @var String
   */
  const POST_TYPE = 'gc_templates';

  /**
   * The directory name to save local JSON to
   *
   * @var String
   */
  const DIRECTORY = 'gc-templates';

  /**
   * The filename format for local JSON items
   *
   * @var String
   */
  const FILENAME_FORMAT = 'template_%s.json';

  /**
   * The template mapping post list column key for the local JSON utility.
   *
   * @var String
   */
  const COLUMN_KEY = 'sync';

  /**
   * The Gather Content column label for the local JSON utility.
   *
   * @var String
   */
  const COLUMN_LABEL = 'Local JSON';

  /**
   * The meta key for the data item used to link items to sync. They symbolic
   * link between GC Template Post and local JSON.
   *
   * @var String
   */
  const SYNC_KEY = '_gc_template';

  /**
   * The label for the default status where no mapping has been created (or no
   * file matching the filename format or key has been found).
   *
   * @var String
   */
  const STATUS_DEFAULT = 'Awaiting save';

  /**
   * The label for the sync action.
   *
   * @var String
   */
  const STATUS_AVAILABLE = 'Sync Available';

  /**
   * The label for an item that has been synced or saved locally and is
   * up-to-date with the local JSON.
   *
   * @var String
   */
  const STATUS_COMPLETE = 'Saved';

  /**
   * String for the import button label.
   *
   * @var String
   */
  const IMPORT_LABEL = 'Import Local JSON Template Mappings';

  /**
   * String for the import button information dialog.
   *
   * @var String
   */
  const IMPORT_MESSAGE = 'There are some local JSON template mappings that are' .
    ' not currently in this list. You may import them by clicking this button.';

  /**
   * Arial label string for the import button information dialog.
   *
   * @var String
   */
  const IMPORT_MESSAGE_ARIA = 'More local JSON mapping information';

  /**
   * Add WordPress filters and actions for local JSON syncing.
   *
   * @return  Class  Constructed instance of $this;
   */
  public function __construct() {
    /** Adds the JSON sync column to the template mapping post list. */
    add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'addSyncColumn'], 11, 1);

    /** Fills the local JSON column in the template mapping table list with the sync action link. */
    add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'addSyncColumnAction'], 10, 2);

    /** Adds the local JSON import button above the template mapping table list */
    add_action('manage_posts_extra_tablenav', [$this, 'addImportAction']);

    /** Endpoint for the local JSON sync action. */
    add_action('admin_action_' . self::ACTION, [$this, 'sync']);

    /** Endpoint for the local JSON import action. */
    add_action('admin_action_' . self::ACTION_IMPORT, [$this, 'import']);

    /** Hooks into the save post action for template mappings to write latest local JSON file. */
    add_action('save_post_' . self::POST_TYPE, [$this, 'writeJson']);

    return $this;
  }

  /**
   * Writes the local Gather Content JSON file.
   *
   * @param   String/Number   $id  The template mapping post ID.
   *
   * @return  Number/Boolean       Response of file_put_contents; number of
   *                               bytes written to file or false if failed.
   */
  public function writeJson($id) {
    $post = get_post($id);

    $uuid = get_post_meta($id, self::SYNC_KEY, 1);

    $gcTemplate = array(
      'id' => $uuid,
      'title' => $post->post_title,
      'meta' => array(
        '_gc_account' => get_post_meta($id, '_gc_account', 1),
        '_gc_project' => get_post_meta($id, '_gc_project', 1),
        '_gc_template' => get_post_meta($id, '_gc_template', 1),
        '_gc_structure_uuid' => get_post_meta($id, '_gc_structure_uuid', 1),
        '_gc_account_id' => get_post_meta($id, '_gc_account_id', 1)
      ),
      'content' => json_decode($post->post_content),
      'modified' => get_post_modified_time('U', true, $id)
    );

    return file_put_contents($this->getJsonPath($uuid), json_encode($gcTemplate, JSON_PRETTY_PRINT));
  }

  /**
   * Adds the Local JSON sync column to the template mapping post list.
   *
   * @param   Array  $columns  Full list of post list columns
   *
   * @return  Array            Modified list of post list columns
   */
  public function addSyncColumn($columns) {
    $columns[self::COLUMN_KEY] = __(self::COLUMN_LABEL);

    return $columns;
  }

  /**
   * Adds the Gather Content JSON sync action to the Local JSON column in the
   * post list.
   *
   * @param   String         $column_name  The key for the Local JSON column.
   * @param   String/Number  $post_id      The post ID for the Local JSON column.
   */
  public function addSyncColumnAction($column_name, $post_id) {
    switch ($column_name) {
      case self::COLUMN_KEY:
        echo $this->getSyncActionOrStatus($post_id);

        break;
    }
  }

  /**
   * Creates the sync action link if the post modified time in the JSON is
   * greater than the posts modified time in the database. Returns status
   * text content if there no JSON or if there is already synced JSON.
   *
   * @param   String/Number  $post_id  The id of the post to create action or status.
   *
   * @return  String                   Link action or text status of JSON sync.
   */
  private function getSyncActionOrStatus($post_id) {
    $action = __(self::STATUS_DEFAULT);

    $uuid = get_post_meta($post_id, self::SYNC_KEY, 1);

    $json = $this->getJson($uuid);

    if ($json && $json->modified > get_post_modified_time('U', true, $post_id)) {
      // Create a safe admin URL and add a NONCE for permissions
      $href = wp_nonce_url(add_query_arg(array(
        'action' => self::ACTION,
        'mapping' => $post_id
      ), get_admin_url(null, 'admin.php')), self::ACTION . '_nonce');

      $label = __(self::STATUS_AVAILABLE);

      $jsSelector = $this->generateJsClass();

      $onClick = $this->generateJsToggle($jsSelector);

      $action = "<a href=\"$href\" $onClick>$label <span class=\"$jsSelector spinner\" style=\"margin: 0\"></span></a>";
    } elseif ($json) {
      $action = __(self::STATUS_COMPLETE);
    }

    return $action;
  }

  /**
   * Verifies the request nonce and fetches the JSON file based on the sync key
   * in the mapping post meta. Updates the post with data from the JSON file
   * then redirects back to the template mapping post view.
   */
  public function sync() {
    /** Verify permissions */
    if (false === wp_verify_nonce($_REQUEST['_wpnonce'], self::ACTION . '_nonce')) {
      exit();
    }

    /**
     * Get Local JSON
     */

    $post_id = $_REQUEST['mapping'];

    $uuid = get_post_meta($post_id, self::SYNC_KEY, 1);

    $json = $this->getJson($uuid);

    /**
     * Update Post
     */

    $id = wp_update_post(array(
      'ID' => $post_id,
      'post_title' => $json->title,
      'post_content' => json_encode($json->content),
      'meta_input' => $json->meta
    ));

    /** Redirect back to mapping post list */
    wp_redirect(get_admin_url(null, 'edit.php?post_type=' . self::POST_TYPE));

    exit();
  }

  /**
   * Checks to see if any the template mapping posts exist with a sync key
   * that matches local JSON items. Displays import local JSON action button
   * if there are no matches.
   *
   * @param  String  $which  Position of the table nav hook.
   */
  public function addImportAction($which) {
    if ($_REQUEST['post_type'] === self::POST_TYPE && $which === 'top') {
      $posts = $this->queryPosts();
      $local = $this->getLocalJson();

      $prop = self::SYNC_KEY;

      $local = array_map(function($json) use ($posts, $prop) {
        $json->matches = false;

        foreach ($posts as $key => $post) {
          if (($json->id === get_post_meta($post->ID, $prop, 1))) {
            $json->matches = $post->ID;
          }
        }

        return $json;
      }, $local);

      $local = array_filter($local, function($json) {
        return (false === $json->matches);
      });

      /**
       * Show import function
       */

      if (false === empty($local)) {
        $label = esc_attr(__(self::IMPORT_LABEL));
        $message = esc_attr(__(self::IMPORT_MESSAGE));
        $ariaLabel = esc_attr(__(self::IMPORT_MESSAGE_ARIA));

        $value = implode(',', array_values(array_map(function($json) {
            return $json->id;
        }, $local)));

        $href = wp_nonce_url(add_query_arg(array(
          'action' => self::ACTION_IMPORT,
          'mapping_import' => $value
        ), get_admin_url(null, 'admin.php')), self::ACTION_IMPORT . '_nonce');

        $jsSelector = $this->generateJsClass();

        $onClick = $this->generateJsToggle($jsSelector);

        echo "<div class=\"alignleft actions\" style=\"display: flex; align-items: center;\">";
        echo "  <a href=\"$href\" class=\"button-primary\" $onClick>$label</a>";
        echo "  &nbsp;";
        echo "  <span class=\"dashicons dashicons-info-outline\"
          style=\"cursor: pointer\" title=\"$message\" aria-label=\"$ariaLabel\"></span>";
        echo "  <span class=\"$jsSelector spinner\" style=\"margin: 0\" ></span>";
        echo "</div>";
      }
    }
  }

  /**
   * Verifies the request nonce and imports local JSON items specified via
   * request parameters. Inserts a new post into the database using JSON data.
   */
  public function import() {
    /** Verify permissions */
    if (false === wp_verify_nonce($_REQUEST['_wpnonce'], self::ACTION_IMPORT . '_nonce')) {
      exit();
    }

    /**
     * Get Local JSON
     */

    $uuids = explode(',', $_REQUEST['mapping_import']);

    foreach ($uuids as $index => $uuid) {
      $json = $this->getJson($uuid);

      /**
       * Create post and update post meta
       */

      $id = wp_insert_post(array(
        'post_type' => self::POST_TYPE,
        'post_title' => $json->title,
        'post_content' => json_encode($json->content),
        'post_status' => 'publish',
        'ping_status' => 'closed',
        'meta_input' => $json->meta
      ));
    }

    /** Redirect back to mapping post list */
    wp_redirect(get_admin_url(null, 'edit.php?post_type=' . self::POST_TYPE));

    exit();
  }

  /**
   * Returns valid files and file contents from the local JSON directory.
   *
   * @return  Array  List of local JSON template mapping content, decoded to PHP.
   */
  private function getLocalJson() {
    $directory = $this->getDirectory();

    /**
     * Get valid files and file contents
     */

    $templates = array_filter(scandir($directory), function($file) {
      return ('json' === pathinfo($file, PATHINFO_EXTENSION));
    });

    $prop = self::SYNC_KEY;

    $templates = array_filter(array_map(function($file) use ($directory, $prop) {
      $json = json_decode(file_get_contents("$directory/$file"));

      return ($json && !empty($json->id) && $json->id === $json->meta->$prop) ?
        json_decode(file_get_contents("$directory/$file")) : false;
    }, $templates));

    return $templates;
  }

  /**
   * Get the GC Mapping posts by the custom meta key used for unique ID.
   *
   * @param   String          $uuid  The mapping structure identifier.
   *
   * @return  Object/Boolean         A WP_Query Post Object or false if no
   *                                 post found.
   */
  private function queryPosts($uuid = false) {
    $params = array(
      'post_type' => self::POST_TYPE,
      'nopaging' => true
    );

    if ($uuid) {
      $params['meta_query'] = [
        array(
          'key' => self::SYNC_KEY,
          'value' => $uuid,
          'compare' => '='
        )
      ];
    }

    $query = new WP_Query($params);

    return ($query->posts) ? $query->posts : false;
  }

  /**
   * Get the local JSON file contents as a PHP object by structure ID.
   *
   * @param   String/Number   $uuid  The structure ID that corresponds with the
   *                                 local JSON file.
   *
   * @return  Object/Boolean         The local JSON converted to PHP, false if
   *                                 the file doesn't exist.
   */
  private function getJson($uuid) {
    $file = $this->getJsonPath($uuid);

    return (file_exists($file)) ? json_decode(file_get_contents($file)) : false;
  }

  /**
   * Construct the local JSON file path string based on class constants and
   * structure ID. If the base directory doesn't exist, it will create the
   * directory.
   *
   * @param   String/Number  $uuid  The mapping structure ID used to identify
   *                                the file.
   *
   * @return  String                The full path within the site's theme
   *                                directory.
   */
  private function getJsonPath($uuid) {
    $directory = $this->getDirectory();

    $file = sprintf(self::FILENAME_FORMAT, $uuid);

    if (false === is_dir($directory)) {
      mkdir($directory);
    }

    return "$directory/$file";
  }

  /**
   * Utility for retrieving the local directory store.
   *
   * @return  String  Local directory path
   */
  private function getDirectory() {
    return get_stylesheet_directory() . '/' . self::DIRECTORY;
  }

  /**
   * Creates a unique class for JavaScript toggling.
   *
   * @return  String  Unique toggling class, prefixed with 'js-'.
   */
  private function generateJsClass() {
    return 'js-' . substr(md5(time()), 0, 8);
  }

  /**
   * Creates a toggling attribute for hiding a spinner.
   *
   * @param   String  $jsSelector  Selector to target
   *
   * @return  String               The full onclick attribute
   */
  private function generateJsToggle($jsSelector) {
    return "onclick=\"document.querySelector('.$jsSelector').classList.add('is-active')\"";
  }
}
