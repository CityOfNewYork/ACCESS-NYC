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
use WPML\Core\Twig\TwigFilter;
class FilterExpression extends \WPML\Core\Twig\Node\Expression\CallExpression
{
    public function __construct(\WPML\Core\Twig_NodeInterface $node, \WPML\Core\Twig\Node\Expression\ConstantExpression $filterName, \WPML\Core\Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        parent::__construct(['node' => $node, 'filter' => $filterName, 'arguments' => $arguments], [], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $name = $this->getNode('filter')->getAttribute('value');
        $filter = $compiler->getEnvironment()->getFilter($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'filter');
        $this->setAttribute('thing', $filter);
        $this->setAttribute('needs_environment', $filter->needsEnvironment());
        $this->setAttribute('needs_context', $filter->needsContext());
        $this->setAttribute('arguments', $filter->getArguments());
        if ($filter instanceof \WPML\Core\Twig_FilterCallableInterface || $filter instanceof \WPML\Core\Twig\TwigFilter) {
            $this->setAttribute('callable', $filter->getCallable());
        }
        if ($filter instanceof \WPML\Core\Twig\TwigFilter) {
            $this->setAttribute('is_variadic', $filter->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\FilterExpression', 'WPML\\Core\\Twig_Node_Expression_Filter');
