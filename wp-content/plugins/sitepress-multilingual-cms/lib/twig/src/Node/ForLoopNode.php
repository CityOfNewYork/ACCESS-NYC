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
 * Internal node used by the for node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ForLoopNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct($lineno, $tag = null)
    {
        parent::__construct([], ['with_loop' => \false, 'ifexpr' => \false, 'else' => \false], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        if ($this->getAttribute('else')) {
            $compiler->write("\$context['_iterated'] = true;\n");
        }
        if ($this->getAttribute('with_loop')) {
            $compiler->write("++\$context['loop']['index0'];\n")->write("++\$context['loop']['index'];\n")->write("\$context['loop']['first'] = false;\n");
            if (!$this->getAttribute('ifexpr')) {
                $compiler->write("if (isset(\$context['loop']['length'])) {\n")->indent()->write("--\$context['loop']['revindex0'];\n")->write("--\$context['loop']['revindex'];\n")->write("\$context['loop']['last'] = 0 === \$context['loop']['revindex0'];\n")->outdent()->write("}\n");
            }
        }
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\ForLoopNode', 'WPML\\Core\\Twig_Node_ForLoop');
