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

use WPML\Core\Twig\Node\Node;
/**
 * Abstract class for all nodes that represents an expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractExpression extends \WPML\Core\Twig\Node\Node
{
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\AbstractExpression', 'WPML\\Core\\Twig_Node_Expression');
