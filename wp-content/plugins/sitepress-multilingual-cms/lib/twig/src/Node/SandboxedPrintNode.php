<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Node;

use WPML\Core\Twig\Compiler;
use WPML\Core\Twig\Node\Expression\ConstantExpression;
use WPML\Core\Twig\Node\Expression\FilterExpression;
/**
 * Adds a check for the __toString() method when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * Not used anymore, to be deprecated in 2.x and removed in 3.0
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxedPrintNode extends \WPML\Core\Twig\Node\PrintNode
{
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ');
        $expr = $this->getNode('expr');
        if ($expr instanceof \WPML\Core\Twig\Node\Expression\ConstantExpression) {
            $compiler->subcompile($expr)->raw(";\n");
        } else {
            $compiler->write('$this->env->getExtension(\'\\WPML\\Core\\Twig\\Extension\\SandboxExtension\')->ensureToStringAllowed(')->subcompile($expr)->raw(");\n");
        }
    }
    /**
     * Removes node filters.
     *
     * This is mostly needed when another visitor adds filters (like the escaper one).
     *
     * @return Node
     */
    protected function removeNodeFilter(\WPML\Core\Twig\Node\Node $node)
    {
        if ($node instanceof \WPML\Core\Twig\Node\Expression\FilterExpression) {
            return $this->removeNodeFilter($node->getNode('node'));
        }
        return $node;
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\SandboxedPrintNode', 'WPML\\Core\\Twig_Node_SandboxedPrint');
