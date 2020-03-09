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
namespace WPML\Core\Twig\Node\Expression\Binary;

use WPML\Core\Twig\Compiler;
class SubBinary extends \WPML\Core\Twig\Node\Expression\Binary\AbstractBinary
{
    public function operator(\WPML\Core\Twig\Compiler $compiler)
    {
        return $compiler->raw('-');
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Binary\\SubBinary', 'WPML\\Core\\Twig_Node_Expression_Binary_Sub');
