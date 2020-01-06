<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Node\Expression;

use WPML\Core\Twig\Compiler;
use WPML\Core\Twig\Node\Expression\Binary\AndBinary;
use WPML\Core\Twig\Node\Expression\Test\DefinedTest;
use WPML\Core\Twig\Node\Expression\Test\NullTest;
use WPML\Core\Twig\Node\Expression\Unary\NotUnary;
use WPML\Core\Twig\Node\Node;
class NullCoalesceExpression extends \WPML\Core\Twig\Node\Expression\ConditionalExpression
{
    public function __construct(\WPML\Core\Twig_NodeInterface $left, \WPML\Core\Twig_NodeInterface $right, $lineno)
    {
        $test = new \WPML\Core\Twig\Node\Expression\Test\DefinedTest(clone $left, 'defined', new \WPML\Core\Twig\Node\Node(), $left->getTemplateLine());
        // for "block()", we don't need the null test as the return value is always a string
        if (!$left instanceof \WPML\Core\Twig\Node\Expression\BlockReferenceExpression) {
            $test = new \WPML\Core\Twig\Node\Expression\Binary\AndBinary($test, new \WPML\Core\Twig\Node\Expression\Unary\NotUnary(new \WPML\Core\Twig\Node\Expression\Test\NullTest($left, 'null', new \WPML\Core\Twig\Node\Node(), $left->getTemplateLine()), $left->getTemplateLine()), $left->getTemplateLine());
        }
        parent::__construct($test, $left, $right, $lineno);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        /*
         * This optimizes only one case. PHP 7 also supports more complex expressions
         * that can return null. So, for instance, if log is defined, log("foo") ?? "..." works,
         * but log($a["foo"]) ?? "..." does not if $a["foo"] is not defined. More advanced
         * cases might be implemented as an optimizer node visitor, but has not been done
         * as benefits are probably not worth the added complexity.
         */
        if (\PHP_VERSION_ID >= 70000 && $this->getNode('expr2') instanceof \WPML\Core\Twig\Node\Expression\NameExpression) {
            $this->getNode('expr2')->setAttribute('always_defined', \true);
            $compiler->raw('((')->subcompile($this->getNode('expr2'))->raw(') ?? (')->subcompile($this->getNode('expr3'))->raw('))');
        } else {
            parent::compile($compiler);
        }
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\NullCoalesceExpression', 'WPML\\Core\\Twig_Node_Expression_NullCoalesce');
