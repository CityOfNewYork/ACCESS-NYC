<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\TokenParser;

use WPML\Core\Twig\Node\DeprecatedNode;
use WPML\Core\Twig\Token;
/**
 * Deprecates a section of a template.
 *
 *    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' %}
 *    {% extends 'layout.html.twig' %}
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @final
 */
class DeprecatedTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\DeprecatedNode($expr, $token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'deprecated';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\DeprecatedTokenParser', 'WPML\\Core\\Twig_TokenParser_Deprecated');
