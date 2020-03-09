<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Node\Expression\Test;

use WPML\Core\Twig\Compiler;
use WPML\Core\Twig\Error\SyntaxError;
use WPML\Core\Twig\Node\Expression\ArrayExpression;
use WPML\Core\Twig\Node\Expression\BlockReferenceExpression;
use WPML\Core\Twig\Node\Expression\ConstantExpression;
use WPML\Core\Twig\Node\Expression\FunctionExpression;
use WPML\Core\Twig\Node\Expression\GetAttrExpression;
use WPML\Core\Twig\Node\Expression\NameExpression;
use WPML\Core\Twig\Node\Expression\TestExpression;
/**
 * Checks if a variable is defined in the current context.
 *
 *    {# defined works with variable names and variable attributes #}
 *    {% if foo is defined %}
 *        {# ... #}
 *    {% endif %}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DefinedTest extends \WPML\Core\Twig\Node\Expression\TestExpression
{
    public function __construct(\WPML\Core\Twig_NodeInterface $node, $name, \WPML\Core\Twig_NodeInterface $arguments = null, $lineno)
    {
        if ($node instanceof \WPML\Core\Twig\Node\Expression\NameExpression) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \WPML\Core\Twig\Node\Expression\GetAttrExpression) {
            $node->setAttribute('is_defined_test', \true);
            $this->changeIgnoreStrictCheck($node);
        } elseif ($node instanceof \WPML\Core\Twig\Node\Expression\BlockReferenceExpression) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \WPML\Core\Twig\Node\Expression\FunctionExpression && 'constant' === $node->getAttribute('name')) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \WPML\Core\Twig\Node\Expression\ConstantExpression || $node instanceof \WPML\Core\Twig\Node\Expression\ArrayExpression) {
            $node = new \WPML\Core\Twig\Node\Expression\ConstantExpression(\true, $node->getTemplateLine());
        } else {
            throw new \WPML\Core\Twig\Error\SyntaxError('The "defined" test only works with simple variables.', $lineno);
        }
        parent::__construct($node, $name, $arguments, $lineno);
    }
    protected function changeIgnoreStrictCheck(\WPML\Core\Twig\Node\Expression\GetAttrExpression $node)
    {
        $node->setAttribute('ignore_strict_check', \true);
        if ($node->getNode('node') instanceof \WPML\Core\Twig\Node\Expression\GetAttrExpression) {
            $this->changeIgnoreStrictCheck($node->getNode('node'));
        }
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Test\\DefinedTest', 'WPML\\Core\\Twig_Node_Expression_Test_Defined');
