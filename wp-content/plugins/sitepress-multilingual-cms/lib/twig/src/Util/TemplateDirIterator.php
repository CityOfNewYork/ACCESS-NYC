<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Util;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TemplateDirIterator extends \IteratorIterator
{
    public function current()
    {
        return \file_get_contents(parent::current());
    }
    public function key()
    {
        return (string) parent::key();
    }
}
\class_alias('WPML\\Core\\Twig\\Util\\TemplateDirIterator', 'WPML\\Core\\Twig_Util_TemplateDirIterator');
