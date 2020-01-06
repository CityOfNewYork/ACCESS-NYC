<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Extension;

use WPML\Core\Twig\Environment;
abstract class AbstractExtension implements \WPML\Core\Twig\Extension\ExtensionInterface
{
    /**
     * @deprecated since 1.23 (to be removed in 2.0), implement \Twig_Extension_InitRuntimeInterface instead
     */
    public function initRuntime(\WPML\Core\Twig\Environment $environment)
    {
    }
    public function getTokenParsers()
    {
        return [];
    }
    public function getNodeVisitors()
    {
        return [];
    }
    public function getFilters()
    {
        return [];
    }
    public function getTests()
    {
        return [];
    }
    public function getFunctions()
    {
        return [];
    }
    public function getOperators()
    {
        return [];
    }
    /**
     * @deprecated since 1.23 (to be removed in 2.0), implement \Twig_Extension_GlobalsInterface instead
     */
    public function getGlobals()
    {
        return [];
    }
    /**
     * @deprecated since 1.26 (to be removed in 2.0), not used anymore internally
     */
    public function getName()
    {
        return \get_class($this);
    }
}
\class_alias('WPML\\Core\\Twig\\Extension\\AbstractExtension', 'WPML\\Core\\Twig_Extension');
