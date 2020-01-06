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
use WPML\Core\Twig\Node\Expression\NameExpression;
/**
 * Represents an import node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ImportNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct(\WPML\Core\Twig\Node\Expression\AbstractExpression $expr, \WPML\Core\Twig\Node\Expression\AbstractExpression $var, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr, 'var' => $var], [], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('')->subcompile($this->getNode('var'))->raw(' = ');
        if ($this->getNode('expr') instanceof \WPML\Core\Twig\Node\Expression\NameExpression && '_self' === $this->getNode('expr')->getAttribute('name')) {
            $compiler->raw('$this');
        } else {
            $compiler->raw('$this->loadTemplate(')->subcompile($this->getNode('expr'))->raw(', ')->repr($this->getTemplateName())->raw(', ')->repr($this->getTemplateLine())->raw(')->unwrap()');
        }
        $compiler->raw(";\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\ImportNode', 'WPML\\Core\\Twig_Node_Import');
