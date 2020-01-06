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

use WPML\Core\Twig\NodeVisitor\EscaperNodeVisitor;
use WPML\Core\Twig\TokenParser\AutoEscapeTokenParser;
use WPML\Core\Twig\TwigFilter;
/**
 * @final
 */
class EscaperExtension extends \WPML\Core\Twig\Extension\AbstractExtension
{
    protected $defaultStrategy;
    /**
     * @param string|false|callable $defaultStrategy An escaping strategy
     *
     * @see setDefaultStrategy()
     */
    public function __construct($defaultStrategy = 'html')
    {
        $this->setDefaultStrategy($defaultStrategy);
    }
    public function getTokenParsers()
    {
        return [new \WPML\Core\Twig\TokenParser\AutoEscapeTokenParser()];
    }
    public function getNodeVisitors()
    {
        return [new \WPML\Core\Twig\NodeVisitor\EscaperNodeVisitor()];
    }
    public function getFilters()
    {
        return [new \WPML\Core\Twig\TwigFilter('raw', 'twig_raw_filter', ['is_safe' => ['all']])];
    }
    /**
     * Sets the default strategy to use when not defined by the user.
     *
     * The strategy can be a valid PHP callback that takes the template
     * name as an argument and returns the strategy to use.
     *
     * @param string|false|callable $defaultStrategy An escaping strategy
     */
    public function setDefaultStrategy($defaultStrategy)
    {
        // for BC
        if (\true === $defaultStrategy) {
            @\trigger_error('Using "true" as the default strategy is deprecated since version 1.21. Use "html" instead.', \E_USER_DEPRECATED);
            $defaultStrategy = 'html';
        }
        if ('filename' === $defaultStrategy) {
            @\trigger_error('Using "filename" as the default strategy is deprecated since version 1.27. Use "name" instead.', \E_USER_DEPRECATED);
            $defaultStrategy = 'name';
        }
        if ('name' === $defaultStrategy) {
            $defaultStrategy = ['WPML\\Core\\Twig\\FileExtensionEscapingStrategy', 'guess'];
        }
        $this->defaultStrategy = $defaultStrategy;
    }
    /**
     * Gets the default strategy to use when not defined by the user.
     *
     * @param string $name The template name
     *
     * @return string|false The default strategy to use for the template
     */
    public function getDefaultStrategy($name)
    {
        // disable string callables to avoid calling a function named html or js,
        // or any other upcoming escaping strategy
        if (!\is_string($this->defaultStrategy) && \false !== $this->defaultStrategy) {
            return \call_user_func($this->defaultStrategy, $name);
        }
        return $this->defaultStrategy;
    }
    public function getName()
    {
        return 'escaper';
    }
}
\class_alias('WPML\\Core\\Twig\\Extension\\EscaperExtension', 'WPML\\Core\\Twig_Extension_Escaper');
namespace WPML\Core;

/**
 * Marks a variable as being safe.
 *
 * @param string $string A PHP variable
 *
 * @return string
 */
function twig_raw_filter($string)
{
    return $string;
}
