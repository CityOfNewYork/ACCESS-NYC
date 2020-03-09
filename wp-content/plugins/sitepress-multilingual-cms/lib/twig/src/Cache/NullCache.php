<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Cache;

/**
 * Implements a no-cache strategy.
 *
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class NullCache implements \WPML\Core\Twig\Cache\CacheInterface
{
    public function generateKey($name, $className)
    {
        return '';
    }
    public function write($key, $content)
    {
    }
    public function load($key)
    {
    }
    public function getTimestamp($key)
    {
        return 0;
    }
}
\class_alias('WPML\\Core\\Twig\\Cache\\NullCache', 'WPML\\Core\\Twig_Cache_Null');
