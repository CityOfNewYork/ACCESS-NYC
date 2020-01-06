<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Node\Expression\Filter;

use WPML\Core\Twig\Compiler;
use WPML\Core\Twig\Node\Expression\ConditionalExpression;
use WPML\Core\Twig\Node\Expression\ConstantExpression;
use WPML\Core\Twig\Node\Expression\FilterExpression;
use WPML\Core\Twig\Node\Expression\GetAttrExpression;
use WPML\Core\Twig\Node\Expression\NameExpression;
use WPML\Core\Twig\Node\Expression\Test\DefinedTest;
use WPML\Core\Twig\Node\Node;
/**
 * Returns the value or the default value when it is undefined or empty.
 *
 *  {{ var.foo|default('foo item on var is not defined') }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DefaultFilter extends \WPML\Core\Twig\Node\Expression\FilterExpression
{
    public function __construct(\WPML\Core\Twig_NodeInterface $node, \WPML\Core\Twig\Node\Expression\ConstantExpression $filterName, \WPML\Core\Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        $default = new \WPML\Core\Twig\Node\Expression\FilterExpression($node, new \WPML\Core\Twig\Node\Expression\ConstantExpression('default', $node->getTemplateLine()), $arguments, $node->getTemplateLine());
        if ('default' === $filterName->getAttribute('value') && ($node instanceof \WPML\Core\Twig\Node\Expression\NameExpression || $node instanceof \WPML\Core\Twig\Node\Expression\GetAttrExpression)) {
            $test = new \WPML\Core\Twig\Node\Expression\Test\DefinedTest(clone $node, 'defined', new \WPML\Core\Twig\Node\Node(), $node->getTemplateLine());
            $false = \count($arguments) ? $arguments->getNode(0) : new \WPML\Core\Twig\Node\Expression\ConstantExpression('', $node->getTemplateLine());
            $node = new \WPML\Core\Twig\Node\Expression\ConditionalExpression($test, $default, $false, $node->getTemplateLine());
        } else {
            $node = $default;
        }
        parent::__construct($node, $filterName, $arguments, $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Filter\\DefaultFilter', 'WPML\\Core\\Twig_Node_Expression_Filter_Default');
