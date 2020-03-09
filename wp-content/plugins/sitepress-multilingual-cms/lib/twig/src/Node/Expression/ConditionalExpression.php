<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Node\Expression;

use WPML\Core\Twig\Compiler;
class ConditionalExpression extends \WPML\Core\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\WPML\Core\Twig\Node\Expression\AbstractExpression $expr1, \WPML\Core\Twig\Node\Expression\AbstractExpression $expr2, \WPML\Core\Twig\Node\Expression\AbstractExpression $expr3, $lineno)
    {
        parent::__construct(['expr1' => $expr1, 'expr2' => $expr2, 'expr3' => $expr3], [], $lineno);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->raw('((')->subcompile($this->getNode('expr1'))->raw(') ? (')->subcompile($this->getNode('expr2'))->raw(') : (')->subcompile($this->getNode('expr3'))->raw('))');
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\ConditionalExpression', 'WPML\\Core\\Twig_Node_Expression_Conditional');
