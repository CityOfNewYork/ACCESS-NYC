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

use WPML\Core\Twig\Node\WithNode;
use WPML\Core\Twig\Token;
/**
 * Creates a nested scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class WithTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $variables = null;
        $only = \false;
        if (!$stream->test(\WPML\Core\Twig\Token::BLOCK_END_TYPE)) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
            $only = $stream->nextIf(\WPML\Core\Twig\Token::NAME_TYPE, 'only');
        }
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideWithEnd'], \true);
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\WithNode($body, $variables, $only, $token->getLine(), $this->getTag());
    }
    public function decideWithEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endwith');
    }
    public function getTag()
    {
        return 'with';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\WithTokenParser', 'WPML\\Core\\Twig_TokenParser_With');
