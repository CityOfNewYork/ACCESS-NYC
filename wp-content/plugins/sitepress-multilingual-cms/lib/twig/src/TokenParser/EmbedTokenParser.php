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

use WPML\Core\Twig\Node\EmbedNode;
use WPML\Core\Twig\Node\Expression\ConstantExpression;
use WPML\Core\Twig\Node\Expression\NameExpression;
use WPML\Core\Twig\Token;
/**
 * Embeds a template.
 *
 * @final
 */
class EmbedTokenParser extends \WPML\Core\Twig\TokenParser\IncludeTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $parent = $this->parser->getExpressionParser()->parseExpression();
        list($variables, $only, $ignoreMissing) = $this->parseArguments();
        $parentToken = $fakeParentToken = new \WPML\Core\Twig\Token(\WPML\Core\Twig\Token::STRING_TYPE, '__parent__', $token->getLine());
        if ($parent instanceof \WPML\Core\Twig\Node\Expression\ConstantExpression) {
            $parentToken = new \WPML\Core\Twig\Token(\WPML\Core\Twig\Token::STRING_TYPE, $parent->getAttribute('value'), $token->getLine());
        } elseif ($parent instanceof \WPML\Core\Twig\Node\Expression\NameExpression) {
            $parentToken = new \WPML\Core\Twig\Token(\WPML\Core\Twig\Token::NAME_TYPE, $parent->getAttribute('name'), $token->getLine());
        }
        // inject a fake parent to make the parent() function work
        $stream->injectTokens([new \WPML\Core\Twig\Token(\WPML\Core\Twig\Token::BLOCK_START_TYPE, '', $token->getLine()), new \WPML\Core\Twig\Token(\WPML\Core\Twig\Token::NAME_TYPE, 'extends', $token->getLine()), $parentToken, new \WPML\Core\Twig\Token(\WPML\Core\Twig\Token::BLOCK_END_TYPE, '', $token->getLine())]);
        $module = $this->parser->parse($stream, [$this, 'decideBlockEnd'], \true);
        // override the parent with the correct one
        if ($fakeParentToken === $parentToken) {
            $module->setNode('parent', $parent);
        }
        $this->parser->embedTemplate($module);
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\EmbedNode($module->getTemplateName(), $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endembed');
    }
    public function getTag()
    {
        return 'embed';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\EmbedTokenParser', 'WPML\\Core\\Twig_TokenParser_Embed');
