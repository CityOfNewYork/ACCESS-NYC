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
namespace WPML\Core\Twig\Node\Expression\Unary;

use WPML\Core\Twig\Compiler;
class PosUnary extends \WPML\Core\Twig\Node\Expression\Unary\AbstractUnary
{
    public function operator(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->raw('+');
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Unary\\PosUnary', 'WPML\\Core\\Twig_Node_Expression_Unary_Pos');
