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
use WPML\Core\Twig\Node\Expression\AbstractExpression;
/**
 * Represents a do node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct(\WPML\Core\Twig\Node\Expression\AbstractExpression $expr, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr], [], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('')->subcompile($this->getNode('expr'))->raw(";\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\DoNode', 'WPML\\Core\\Twig_Node_Do');
