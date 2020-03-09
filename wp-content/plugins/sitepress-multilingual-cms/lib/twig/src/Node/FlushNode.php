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
 * Represents a flush node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FlushNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct($lineno, $tag)
    {
        parent::__construct([], [], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write("flush();\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\FlushNode', 'WPML\\Core\\Twig_Node_Flush');
