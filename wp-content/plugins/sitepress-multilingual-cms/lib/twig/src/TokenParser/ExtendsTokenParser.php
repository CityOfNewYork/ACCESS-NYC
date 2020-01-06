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
namespace WPML\Core\Twig\TokenParser;

use WPML\Core\Twig\Error\SyntaxError;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Token;
/**
 * Extends a template by another one.
 *
 *  {% extends "base.html" %}
 *
 * @final
 */
class ExtendsTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        if ($this->parser->peekBlockStack()) {
            throw new \WPML\Core\Twig\Error\SyntaxError('Cannot use "extend" in a block.', $token->getLine(), $stream->getSourceContext());
        } elseif (!$this->parser->isMainScope()) {
            throw new \WPML\Core\Twig\Error\SyntaxError('Cannot use "extend" in a macro.', $token->getLine(), $stream->getSourceContext());
        }
        if (null !== $this->parser->getParent()) {
            throw new \WPML\Core\Twig\Error\SyntaxError('Multiple extends tags are forbidden.', $token->getLine(), $stream->getSourceContext());
        }
        $this->parser->setParent($this->parser->getExpressionParser()->parseExpression());
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\Node();
    }
    public function getTag()
    {
        return 'extends';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\ExtendsTokenParser', 'WPML\\Core\\Twig_TokenParser_Extends');
