<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Node\Expression\Binary;

use WPML\Core\Twig\Compiler;
class InBinary extends \WPML\Core\Twig\Node\Expression\Binary\AbstractBinary
{
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->raw('twig_in_filter(')->subcompile($this->getNode('left'))->raw(', ')->subcompile($this->getNode('right'))->raw(')');
    }
    public function operator(\WPML\Core\Twig\Compiler $compiler)
    {
        return $compiler->raw('in');
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Binary\\InBinary', 'WPML\\Core\\Twig_Node_Expression_Binary_In');
