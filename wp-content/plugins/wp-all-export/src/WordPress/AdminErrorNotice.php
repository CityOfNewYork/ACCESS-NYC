<?php

namespace Wpae\WordPress;


class AdminErrorNotice extends AdminNotice
{
    public function getType()
    {
        return 'error';
    }
}