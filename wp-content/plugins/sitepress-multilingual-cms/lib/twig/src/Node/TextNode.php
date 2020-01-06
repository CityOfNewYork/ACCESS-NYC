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
 * Represents a text node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TextNode extends \WPML\Core\Twig\Node\Node implements \WPML\Core\Twig\Node\NodeOutputInterface
{
    public function __construct($data, $lineno)
    {
        parent::__construct([], ['data' => $data], $lineno);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ')->string($this->getAttribute('data'))->raw(";\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\TextNode', 'WPML\\Core\\Twig_Node_Text');
