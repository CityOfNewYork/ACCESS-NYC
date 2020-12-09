<?php

namespace Wpae\WordPress;


abstract class AdminNotice
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function showNotice()
    {
        ?>
        <div class="<?php echo $this->getType();?>"><p>
                <?php echo $this->message; ?>
            </p></div>
        <?php
    }

    public function render()
    {
        add_action('admin_notices', array($this, 'showNotice'));
    }

    abstract function getType();
}