<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Node\Expression\Test;

use WPML\Core\Twig\Compiler;
use WPML\Core\Twig\Node\Expression\TestExpression;
/**
 * Checks if a number is even.
 *
 *  {{ var is even }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class EvenTest extends \WPML\Core\Twig\Node\Expression\TestExpression
{
    public function compile(\WPML\Core\Twig\Compiler $compiler)
    {
        $compiler->raw('(')->subcompile($this->getNode('node'))->raw(' % 2 == 0')->raw(')');
    }
}
\class_alias('WPML\\Core\\Twig\\Node\\Expression\\Test\\EvenTest', 'WPML\\Core\\Twig_Node_Expression_Test_Even');
