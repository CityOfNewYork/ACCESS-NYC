<?php
namespace EnableMediaReplace\Notices;

class NoticeModel //extends ShortPixelModel
{
  public $message; // The message we want to convey.
  public $details = array(); // extra details, like the files involved. Something could be hideable in the future.
  public $code;

  private $id = null; // used for persistent messages.
  protected $viewed = false; // was this notice viewed?
  protected $is_persistent = false;  // This is a fatal issue, display until something was fixed.
  protected $is_dismissed = false; // for persistent notices,
  protected $suppress_until = null;
  protected $suppress_period = -1;
  public $is_removable = true; // if removable, display a notice dialog with red X or so.
  public $messageType = self::NOTICE_NORMAL;

  public $notice_action; // empty unless for display. Ajax action to talk back to controller.
  protected $callback; // empty unless callback is needed

  public static $icons = array();

  const NOTICE_NORMAL = 1;
  const NOTICE_ERROR = 2;
  const NOTICE_SUCCESS = 3;
  const NOTICE_WARNING = 4;

  /** Use this model in conjunction with NoticeController, do not call directly */
  public function __construct($message, $messageType = self::NOTICE_NORMAL)
  {
      $this->message = $message;
      $this->messageType = $messageType;

  }

  public function isDone()
  {
    // check suppressed
    if ($this->is_dismissed && ! is_null($this->suppress_until))
    {
        if (time() >= $this->suppress_until)
        {
            //Log::addDebug('')
            $this->is_persistent = false; // unpersist, so it will be cleaned and dropped.

        }
    }

    if ($this->viewed && ! $this->is_persistent)
      return true;
    else
      return false;
  }

  public function getID()
  {
     return $this->id;
  }

  public function isPersistent()
  {
     return $this->is_persistent;
  }

  public function isDismissed()
  {
    return $this->is_dismissed;
  }

  public function dismiss()
  {
     $this->is_dismissed = true;
     $this->suppress_until = time() + $this->suppress_period;
  }

  public function unDismiss()
  {
    $this->is_dismissed = false;
  }

  public function setDismissedUntil($timestamp)
  {
    $this->suppress_until = $timestamp;
  }

  /** Support for extra information beyond the message.
  * Can help to not overwhelm users w/ the same message but different file /circumstances.
   */
  public function addDetail($detail, $clean = false)
  {
      if ($clean)
        $this->details = array();

      if (! in_array($detail, $this->details) )
        $this->details[] = $detail;
  }



  /** Set a notice persistent. Meaning it shows every page load until dismissed.
  * @param $key Unique Key of this message. Required
  * @param $suppress When dismissed do not show this message again for X amount of time. When -1 it will just be dropped from the Notices and not suppressed
  */
  public function setPersistent($key, $suppress = -1, $callback = null)
  {
      $this->id = $key;
      $this->is_persistent = true;
      $this->suppress_period = $suppress;
      if ( ! is_null($callback) && is_callable($callback))
      {
        $this->callback = $callback;
      }
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

  private function checkIncomplete($var)
  {
     return ($var instanceof \__PHP_Incomplete_Class);
  }

  public function getForDisplay()
  {
    $this->viewed = true;
    $class = 'shortpixel notice ';

    $icon = '';

    if ($this->callback)
    {
      if (is_array($this->callback))
      {
        foreach($this->callback as $part)
        {
          if ($this->checkIncomplete($part) === true)
          {
              return false;
          }
        }
      } elseif (is_object($this->callback))
      {
            if ($this->checkIncomplete($part) === true)
              return false;
      }

       $return = call_user_func($this->callback, $this);
       if ($return === false) // don't display is callback returns false explicitly.
        return;
    }

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


    if ($this->is_removable)
    {
      $class .= 'is-dismissible ';
    }

    if ($this->is_persistent)
    {
      $class .= 'is-persistent ';
    }

    $id = ! is_null($this->id) ?  $this->id : uniqid();
    //'id="' . $this->id . '"'
    $output = "<div id='$id' class='$class'><span class='icon'> " . $icon . "</span> <span class='content'>" . $this->message;
    if ($this->hasDetails())
    {
      $output .= '<div class="details-wrapper">
      <input type="checkbox" name="detailhider" id="check-' . $id .'">
      <label for="check-' . $id . '"  class="show-details"><span>' . __('See Details', 'shortpixel-image-optimiser')   . '</span>
      </label>';

      $output .= "<div class='detail-content-wrapper'><p class='detail-content'>" . $this->parseDetails() . "</p></div>";
      $output .= '<label for="check-' . $id . '" class="hide-details"><span>' . __('Hide Details', 'shortpixel-image-optimiser') . '</span></label>';

      $output .= '</div>'; // detail rapper

    }
    $output .= "</span></div>";

    if ($this->is_persistent && $this->is_removable)
    {
        $output .= "<script type='text/javascript'>\n" . $this->getDismissJS() . "\n</script>";
    }
    return $output;

  }

  protected function hasDetails()
  {
     if (is_array($this->details) && count($this->details) > 0)
      return true;
    else
      return false;
  }

  protected function parseDetails()
  {
      return implode('<BR>', $this->details);
  }

  private function getDismissJS()
  {
     $url = wp_json_encode(admin_url('admin-ajax.php'));
    // $action = 'dismiss';
    $nonce = wp_create_nonce('dismiss');

    $data = wp_json_encode(array('action' => $this->notice_action, 'plugin_action' => 'dismiss', 'nonce' => $nonce, 'id' => $this->id, 'time' => $this->suppress_period));

  //  $data_string = "{action:'$this->notice_action'}";

      $js = "jQuery(document).on('click','#$this->id button.notice-dismiss',
         function() {
           var data = $data;
           var url = $url;
           jQuery.post(url, data); }
      );";
      return "\n jQuery(document).ready(function(){ \n" . $js . "\n});";
  }

}
