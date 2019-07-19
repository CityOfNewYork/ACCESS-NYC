<?php
namespace EnableMediaReplace\Notices;

class NoticeModel //extends ShortPixelModel
{
  protected $message;
  public $code;

  protected $viewed = false;
  public $is_persistent = false;  // This is a fatal issue, display until something was fixed.
  public $is_removable = true; // if removable, display a notice dialog with red X or so.
  public $messageType = self::NOTICE_NORMAL;

  public static $icons = array();

  const NOTICE_NORMAL = 1;
  const NOTICE_ERROR = 2;
  const NOTICE_SUCCESS = 3;
  const NOTICE_WARNING = 4;


  public function __construct($message, $messageType = self::NOTICE_NORMAL)
  {
      $this->message = $message;
      $this->messageType = $messageType;

  }

  public function isDone()
  {
    if ($this->viewed && ! $this->is_persistent)
      return true;
    else
      return false;

  }

  public static function setIcon($notice_type, $icon)
  {
    switch($notice_type)
    {
      case 'error':
        $type = self::NOTICE_ERROR;
      break;
      case 'success':
        $type = self::NOTICE_SUCCESS;
      break;
      case 'warning':
        $type = self::NOTICE_WARNING;
      break;
      case 'normal':
      default:
        $type = self::NOTICE_NORMAL;
      break;
    }
    self::$icons[$type] = $icon;
  }

  public function getForDisplay()
  {
    $this->viewed = true;
    $class = 'shortpixel notice ';

    $icon = '';

    switch($this->messageType)
    {
      case self::NOTICE_ERROR:
        $class .= 'notice-error ';
        $icon = isset(self::$icons[self::NOTICE_ERROR]) ? self::$icons[self::NOTICE_ERROR] : '';
        //$icon = 'scared';
      break;
      case self::NOTICE_SUCCESS:
        $class .= 'notice-success ';
        $icon = isset(self::$icons[self::NOTICE_SUCCESS]) ? self::$icons[self::NOTICE_SUCCESS] : '';
      break;
      case self::NOTICE_WARNING:
        $class .= 'notice-warning ';
        $icon = isset(self::$icons[self::NOTICE_WARNING]) ? self::$icons[self::NOTICE_WARNING] : '';
      break;
      case self::NOTICE_NORMAL:
         $class .= 'notice-info ';
         $icon = isset(self::$icons[self::NOTICE_NORMAL]) ? self::$icons[self::NOTICE_NORMAL] : '';
      break;
      default:
        $class .= 'notice-info ';
        $icon = '';
      break;
    }

    /*$image =  '<img src="' . plugins_url('/shortpixel-image-optimiser/res/img/robo-' . $icon . '.png') . '"
             srcset="' . plugins_url( 'shortpixel-image-optimiser/res/img/robo-' . $icon . '.png' ) . ' 1x, ' . plugins_url( 'shortpixel-image-optimiser/res/img/robo-' . $icon . '@2x.png') . ' 2x" class="short-pixel-notice-icon">';
    */

    if ($this->is_removable)
    {
      $class .= 'is-dismissible ';
    }

    if ($this->is_persistent)
    {
      $class .= '';
    }

    return "<div class='$class'>" . $icon . "<p>" . $this->message . "</p></div>";

  }



  // @todo Transient save, since that is used in some parts.
  // save
  // load


}
