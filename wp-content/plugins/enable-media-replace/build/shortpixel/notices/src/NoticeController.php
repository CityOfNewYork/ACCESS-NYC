<?php
namespace EnableMediaReplace\Notices;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;

class NoticeController //extends ShortPixelController
{
  protected static $notices = array();
  protected static $instance = null;
  protected static $cssHookLoaded = false; // prevent css output more than once.

  public $notice_count = 0;

  protected $has_stored = false;

  protected $notice_option = ''; // The wp_options name for notices here.

  /** For backward compat. Never call constructor directly. */
  public function __construct()
  {
  //    $this->loadModel('notice');
      $ns = __NAMESPACE__;
      $ns = substr($ns, 0, strpos($ns, '\\')); // try to get first part of namespace
      $this->notice_option = $ns . '-notices';

      $this->loadNotices();
      //$this->loadConfig();
  }

  /** Load Notices Config File, if any
  *
  * [ Future Use ]
  */
  public function loadConfig()
  {
    if (file_exists('../notice_config.json'))
    {
      $config = file_get_contents('../notice_config.json');
      $json_config = json_decode($config);
    }
  }

  public function loadIcons($icons)
  {
      foreach($icons as $name => $icon)
        NoticeModel::setIcon($name, $icon);
  }


  protected function loadNotices()
  {
    $notices = get_option($this->notice_option, false);
    $cnotice = (is_array($notices)) ? count($notices) : 0;
    Log::addDebug('Notice Control - #num notices' . $cnotice);
    if ($notices !== false)
    {
      self::$notices = $notices;
      $this->has_stored = true;
    }
    else {
      self::$notices = array();
      $this->has_stored = false;
    }
    $this->countNotices();
  }


  public function addNotice($message, $code, $unique)
  {
      $notice = new NoticeModel($message, $code);

      if ($unique)
      {
        foreach(self::$notices as $nitem)
        {
          if ($nitem->message == $notice->message && $nitem->code == $notice->code) // same message.
            return false;
        }
      }
      self::$notices[] = $notice;
      $this->countNotices();
      Log::addDebug('Adding notice - ', $notice);
      $this->update();
      return $notice;
  }

  /** Update the notices to store, check what to remove, returns count.  */
  public function update()
  {
    if (! is_array(self::$notices) || count(self::$notices) == 0)
    {
      if ($this->has_stored)
        delete_option($this->notice_option);

      return 0;
    }

    $new_notices = array();
    foreach(self::$notices as $item)
    {
      if (! $item->isDone() )
      {
        $new_notices[] = $item;
      }
    }

    update_option($this->notice_option, $new_notices);
    self::$notices = $new_notices;

    return $this->countNotices();
  }

  public function countNotices()
  {
      $this->notice_count = count(self::$notices);
      return $this->notice_count;
  }


  public function getNotices()
  {
        return self::$notices;
  }

  public static function getInstance()
  {
     if ( self::$instance === null)
     {
         self::$instance = new NoticeController();
     }

     return self::$instance;
  }

  /** Adds a notice, quick and fast method
  * @param String $message The Message you want to notify
  * @param Boolean $unique If unique, check to not repeat notice exact same text in notices.  Discard if so
  * @param int $code A value of messageType as defined in model
  * @returm Object Instance of noticeModel
  */

  public static function addNormal($message, $unique = false)
  {
    $noticeController = self::getInstance();
    $notice = $noticeController->addNotice($message, NoticeModel::NOTICE_NORMAL, $unique);
    return $notice;

  }

  public static function addError($message, $unique = false)
  {
    $noticeController = self::getInstance();
    $notice = $noticeController->addNotice($message, NoticeModel::NOTICE_ERROR, $unique);
    return $notice;

  }

  public static function addWarning($message, $unique = false)
  {
    $noticeController = self::getInstance();
    $notice = $noticeController->addNotice($message, NoticeModel::NOTICE_WARNING, $unique);
    return $notice;

  }

  public static function addSuccess($message, $unique = false)
  {
    $noticeController = self::getInstance();
    $notice = $noticeController->addNotice($message, NoticeModel::NOTICE_SUCCESS, $unique);
    return $notice;

  }

  public function admin_notices()
  {
      if ($this->countNotices() > 0)
      {
          if (! self::$cssHookLoaded)
          {
            add_action('admin_print_footer_scripts', array($this, 'printNoticeStyle'));
            self::$cssHookLoaded = true;
          }
          foreach($this->getNotices() as $notice)
          {
            echo $notice->getForDisplay();
          }
      }
      $this->update(); // puts views, and updates
  }

  public function printNoticeStyle()
  {
     if (file_exists(__DIR__ . '/css/notices.css'))
     {
       echo '<style>' . file_get_contents(__DIR__ . '/css/notices.css') . '</style>';
     }
     else {
       Log::addDebug('Notices : css/notices.css could not be loaded');
     }
  }




}
