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

use WPML\Core\Twig\NodeVisitor\NodeVisitorInterface;
use WPML\Core\Twig\TokenParser\TokenParserInterface;
/**
 * Internal class.
 *
 * This class is used by \Twig\Environment as a staging area and must not be used directly.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
class StagingExtension extends \WPML\Core\Twig\Extension\AbstractExtension
{
    protected $functions = [];
    protected $filters = [];
    protected $visitors = [];
    protected $tokenParsers = [];
    protected $globals = [];
    protected $tests = [];
    public function addFunction($name, $function)
    {
        if (isset($this->functions[$name])) {
            @\trigger_error(\sprintf('Overriding function "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $name), \E_USER_DEPRECATED);
        }
        $this->functions[$name] = $function;
    }
    public function getFunctions()
    {
        return $this->functions;
    }
    public function addFilter($name, $filter)
    {
        if (isset($this->filters[$name])) {
            @\trigger_error(\sprintf('Overriding filter "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $name), \E_USER_DEPRECATED);
        }
        $this->filters[$name] = $filter;
    }
    public function getFilters()
    {
        return $this->filters;
    }
    public function addNodeVisitor(\WPML\Core\Twig\NodeVisitor\NodeVisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }
    public function getNodeVisitors()
    {
        return $this->visitors;
    }
    public function addTokenParser(\WPML\Core\Twig\TokenParser\TokenParserInterface $parser)
    {
        if (isset($this->tokenParsers[$parser->getTag()])) {
            @\trigger_error(\sprintf('Overriding tag "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $parser->getTag()), \E_USER_DEPRECATED);
        }
        $this->tokenParsers[$parser->getTag()] = $parser;
    }
    public function getTokenParsers()
    {
        return $this->tokenParsers;
    }
    public function addGlobal($name, $value)
    {
        $this->globals[$name] = $value;
    }
    public function getGlobals()
    {
        return $this->globals;
    }
    public function addTest($name, $test)
    {
        if (isset($this->tests[$name])) {
            @\trigger_error(\sprintf('Overriding test "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $name), \E_USER_DEPRECATED);
        }
        $this->tests[$name] = $test;
    }
    public function getTests()
    {
        return $this->tests;
    }
    public function getName()
    {
        return 'staging';
    }
}
\class_alias('WPML\\Core\\Twig\\Extension\\StagingExtension', 'WPML\\Core\\Twig_Extension_Staging');
