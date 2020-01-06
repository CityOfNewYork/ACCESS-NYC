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
/**
 * Represents a sandbox node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct(\WPML\Core\Twig_NodeInterface $body, $lineno, $tag = null)
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write("if (!\$alreadySandboxed = \$this->sandbox->isSandboxed()) {\n")->indent()->write("\$this->sandbox->enableSandbox();\n")->outdent()->write("}\n")->subcompile($this->getNode('body'))->write("if (!\$alreadySandboxed) {\n")->indent()->write("\$this->sandbox->disableSandbox();\n")->outdent()->write("}\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\SandboxNode', 'WPML\\Core\\Twig_Node_Sandbox');
