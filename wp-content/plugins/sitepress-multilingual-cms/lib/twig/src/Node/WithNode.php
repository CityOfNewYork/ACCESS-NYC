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
 * Represents a nested "with" scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class WithNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct(\WPML\Core\Twig\Node\Node $body, \WPML\Core\Twig\Node\Node $variables = null, $only = \false, $lineno, $tag = null)
    {
        $nodes = ['body' => $body];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }
        parent::__construct($nodes, ['only' => (bool) $only], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        if ($this->hasNode('variables')) {
            $node = $this->getNode('variables');
            $varsName = $compiler->getVarName();
            $compiler->write(\sprintf('$%s = ', $varsName))->subcompile($node)->raw(";\n")->write(\sprintf("if (!twig_test_iterable(\$%s)) {\n", $varsName))->indent()->write("throw new RuntimeError('Variables passed to the \"with\" tag must be a hash.', ")->repr($node->getTemplateLine())->raw(", \$this->getSourceContext());\n")->outdent()->write("}\n")->write(\sprintf("\$%s = twig_to_array(\$%s);\n", $varsName, $varsName));
            if ($this->getAttribute('only')) {
                $compiler->write("\$context = ['_parent' => \$context];\n");
            } else {
                $compiler->write("\$context['_parent'] = \$context;\n");
            }
            $compiler->write(\sprintf("\$context = \$this->env->mergeGlobals(array_merge(\$context, \$%s));\n", $varsName));
        } else {
            $compiler->write("\$context['_parent'] = \$context;\n");
        }
        $compiler->subcompile($this->getNode('body'))->write("\$context = \$context['_parent'];\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\WithNode', 'WPML\\Core\\Twig_Node_With');
