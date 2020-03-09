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
use WPML\Core\Twig\Node\Expression\ConstantExpression;
/**
 * Represents a deprecated node.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class DeprecatedNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct(\WPML\Core\Twig\Node\Expression\AbstractExpression $expr, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr], [], $lineno, $tag);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        $expr = $this->getNode('expr');
        if ($expr instanceof \WPML\Core\Twig\Node\Expression\ConstantExpression) {
            $compiler->write('@trigger_error(')->subcompile($expr);
        } else {
            $varName = $compiler->getVarName();
            $compiler->write(\sprintf('$%s = ', $varName))->subcompile($expr)->raw(";\n")->write(\sprintf('@trigger_error($%s', $varName));
        }
        $compiler->raw('.')->string(\sprintf(' ("%s" at line %d).', $this->getTemplateName(), $this->getTemplateLine()))->raw(", E_USER_DEPRECATED);\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\DeprecatedNode', 'WPML\\Core\\Twig_Node_Deprecated');
