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
class MatchesBinary extends \WPML\Core\Twig\Node\Expression\Binary\AbstractBinary
{
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->raw('preg_match(')->subcompile($this->getNode('right'))->raw(', ')->subcompile($this->getNode('left'))->raw(')');
    }
    public function operator(\WPML\Core\Twig\Compiler $compiler)
    {
        return $compiler->raw('');
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Binary\\MatchesBinary', 'WPML\\Core\\Twig_Node_Expression_Binary_Matches');
