<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Profiler\Node;

use WPML\Core\Twig\Compiler;
use WPML\Core\Twig\Node\Node;
/**
 * Represents a profile enter node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class EnterProfileNode extends \WPML\Core\Twig\Node\Node
{
    public function __construct($extensionName, $type, $name, $varName)
    {
        parent::__construct([], ['extension_name' => $extensionName, 'name' => $name, 'type' => $type, 'var_name' => $varName]);
    }
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->write(\sprintf('$%s = $this->env->getExtension(', $this->getAttribute('var_name')))->repr($this->getAttribute('extension_name'))->raw(");\n")->write(\sprintf('$%s->enter($%s = new \\Twig\\Profiler\\Profile($this->getTemplateName(), ', $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'))->repr($this->getAttribute('type'))->raw(', ')->repr($this->getAttribute('name'))->raw("));\n\n");
    }
}
\class_alias('WPML\\Core\\Twig\\Profiler\\Node\\EnterProfileNode', 'WPML\\Core\\Twig_Profiler_Node_EnterProfile');
