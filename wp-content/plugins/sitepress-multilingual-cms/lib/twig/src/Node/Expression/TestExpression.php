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
use WPML\Core\Twig\TwigTest;
class TestExpression extends \WPML\Core\Twig\Node\Expression\CallExpression
{
    public function __construct(\WPML\Core\Twig_NodeInterface $node, $name, \WPML\Core\Twig_NodeInterface $arguments = null, $lineno)
    {
        $nodes = ['node' => $node];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }
        parent::__construct($nodes, ['name' => $name], $lineno);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $test = $compiler->getEnvironment()->getTest($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'test');
        $this->setAttribute('thing', $test);
        if ($test instanceof \WPML\Core\Twig\TwigTest) {
            $this->setAttribute('arguments', $test->getArguments());
        }
        if ($test instanceof \WPML\Core\Twig_TestCallableInterface || $test instanceof \WPML\Core\Twig\TwigTest) {
            $this->setAttribute('callable', $test->getCallable());
        }
        if ($test instanceof \WPML\Core\Twig\TwigTest) {
            $this->setAttribute('is_variadic', $test->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\TestExpression', 'WPML\\Core\\Twig_Node_Expression_Test');
