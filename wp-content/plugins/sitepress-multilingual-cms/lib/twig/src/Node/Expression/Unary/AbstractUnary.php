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
namespace WPML\Core\Twig\Node\Expression\Unary;

use WPML\Core\Twig\Compiler;
use WPML\Core\Twig\Node\Expression\AbstractExpression;
abstract class AbstractUnary extends \WPML\Core\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\WPML\Core\Twig_NodeInterface $node, $lineno)
    {
        parent::__construct(['node' => $node], [], $lineno);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->raw(' ');
        $this->operator($compiler);
        $compiler->subcompile($this->getNode('node'));
    }
    public abstract function operator(\WPML\Core\Twig\Compiler $compiler);
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Unary\\AbstractUnary', 'WPML\\Core\\Twig_Node_Expression_Unary');
