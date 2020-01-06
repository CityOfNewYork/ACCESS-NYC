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
use WPML\Core\Twig\Node\SetNode;
use WPML\Core\Twig\Token;
/**
 * Defines a variable.
 *
 *  {% set foo = 'foo' %}
 *  {% set foo = [1, 2] %}
 *  {% set foo = {'foo': 'bar'} %}
 *  {% set foo = 'foo' ~ 'bar' %}
 *  {% set foo, bar = 'foo', 'bar' %}
 *  {% set foo %}Some content{% endset %}
 *
 * @final
 */
class SetTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $names = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $capture = \false;
        if ($stream->nextIf(\WPML\Core\Twig\Token::OPERATOR_TYPE, '=')) {
            $values = $this->parser->getExpressionParser()->parseMultitargetExpression();
            $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
            if (\count($names) !== \count($values)) {
                throw new \WPML\Core\Twig\Error\SyntaxError('When using set, you must have the same number of variables and assignments.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        } else {
            $capture = \true;
            if (\count($names) > 1) {
                throw new \WPML\Core\Twig\Error\SyntaxError('When using set with a block, you cannot have a multi-target.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
            $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
            $values = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
            $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        }
        return new \WPML\Core\Twig\Node\SetNode($capture, $names, $values, $lineno, $this->getTag());
    }
    public function decideBlockEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endset');
    }
    public function getTag()
    {
        return 'set';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\SetTokenParser', 'WPML\\Core\\Twig_TokenParser_Set');
