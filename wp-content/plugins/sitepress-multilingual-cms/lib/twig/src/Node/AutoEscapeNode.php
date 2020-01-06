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
 * Represents an autoescape node.
 *
 * The value is the escaping strategy (can be html, js, ...)
 *
 * The true value is equivalent to html.
 *
 * If autoescaping is disabled, then the value is false.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AutoEscapeNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct($value, \WPML\Core\Twig_NodeInterface $body, $lineno, $tag = 'autoescape')
    {
        parent::__construct(['body' => $body], ['value' => $value], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('body'));
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\AutoEscapeNode', 'WPML\\Core\\Twig_Node_AutoEscape');
