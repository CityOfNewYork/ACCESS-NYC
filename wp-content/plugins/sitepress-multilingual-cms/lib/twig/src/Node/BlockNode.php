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
namespace WPML\Core\Twig\Node;

use WPML\Core\Twig\Compiler;
/**
 * Represents a block node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class BlockNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct($name, \WPML\Core\Twig_NodeInterface $body, $lineno, $tag = null)
    {
        parent::__construct(['body' => $body], ['name' => $name], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write(\sprintf("public function block_%s(\$context, array \$blocks = [])\n", $this->getAttribute('name')), "{\n")->indent();
        $compiler->subcompile($this->getNode('body'))->outdent()->write("}\n\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\BlockNode', 'WPML\\Core\\Twig_Node_Block');
