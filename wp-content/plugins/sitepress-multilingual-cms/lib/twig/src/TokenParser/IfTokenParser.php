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
use WPML\Core\Twig\Node\IfNode;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Token;
/**
 * Tests a condition.
 *
 *   {% if users %}
 *    <ul>
 *      {% for user in users %}
 *        <li>{{ user.username|e }}</li>
 *      {% endfor %}
 *    </ul>
 *   {% endif %}
 *
 * @final
 */
class IfTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideIfFork']);
        $tests = [$expr, $body];
        $else = null;
        $end = \false;
        while (!$end) {
            switch ($stream->next()->getValue()) {
                case 'else':
                    $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
                    $else = $this->parser->subparse([$this, 'decideIfEnd']);
                    break;
                case 'elseif':
                    $expr = $this->parser->getExpressionParser()->parseExpression();
                    $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse([$this, 'decideIfFork']);
                    $tests[] = $expr;
                    $tests[] = $body;
                    break;
                case 'endif':
                    $end = \true;
                    break;
                default:
                    throw new \WPML\Core\Twig\Error\SyntaxError(\sprintf('Unexpected end of template. Twig was looking for the following tags "else", "elseif", or "endif" to close the "if" block started at line %d).', $lineno), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\IfNode(new \WPML\Core\Twig\Node\Node($tests), $else, $lineno, $this->getTag());
    }
    public function decideIfFork(\WPML\Core\Twig\Token $token)
    {
        return $token->test(['elseif', 'else', 'endif']);
    }
    public function decideIfEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test(['endif']);
    }
    public function getTag()
    {
        return 'if';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\IfTokenParser', 'WPML\\Core\\Twig_TokenParser_If');
