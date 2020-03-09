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

use WPML\Core\Twig\Error\SyntaxError;
use WPML\Core\Twig\Node\Expression\ConstantExpression;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Token;
/**
 * Imports blocks defined in another template into the current template.
 *
 *    {% extends "base.html" %}
 *
 *    {% use "blocks.html" %}
 *
 *    {% block title %}{% endblock %}
 *    {% block content %}{% endblock %}
 *
 * @see https://twig.symfony.com/doc/templates.html#horizontal-reuse for details.
 *
 * @final
 */
class UseTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $template = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        if (!$template instanceof \WPML\Core\Twig\Node\Expression\ConstantExpression) {
            throw new \WPML\Core\Twig\Error\SyntaxError('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        $targets = [];
        if ($stream->nextIf('with')) {
            do {
                $name = $stream->expect(\WPML\Core\Twig\Token::NAME_TYPE)->getValue();
                $alias = $name;
                if ($stream->nextIf('as')) {
                    $alias = $stream->expect(\WPML\Core\Twig\Token::NAME_TYPE)->getValue();
                }
                $targets[$name] = new \WPML\Core\Twig\Node\Expression\ConstantExpression($alias, -1);
                if (!$stream->nextIf(\WPML\Core\Twig\Token::PUNCTUATION_TYPE, ',')) {
                    break;
                }
            } while (\true);
        }
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $this->parser->addTrait(new \WPML\Core\Twig\Node\Node(['template' => $template, 'targets' => new \WPML\Core\Twig\Node\Node($targets)]));
        return new \WPML\Core\Twig\Node\Node();
    }
    public function getTag()
    {
        return 'use';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\UseTokenParser', 'WPML\\Core\\Twig_TokenParser_Use');
