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
use WPML\Core\Twig\Node\BlockNode;
use WPML\Core\Twig\Node\BlockReferenceNode;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Node\PrintNode;
use WPML\Core\Twig\Token;
/**
 * Marks a section of a template as being reusable.
 *
 *  {% block head %}
 *    <link rel="stylesheet" href="style.css" />
 *    <title>{% block title %}{% endblock %} - My Webpage</title>
 *  {% endblock %}
 *
 * @final
 */
class BlockTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(\WPML\Core\Twig\Token::NAME_TYPE)->getValue();
        if ($this->parser->hasBlock($name)) {
            throw new \WPML\Core\Twig\Error\SyntaxError(\sprintf("The block '%s' has already been defined line %d.", $name, $this->parser->getBlock($name)->getTemplateLine()), $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        $this->parser->setBlock($name, $block = new \WPML\Core\Twig\Node\BlockNode($name, new \WPML\Core\Twig\Node\Node([]), $lineno));
        $this->parser->pushLocalScope();
        $this->parser->pushBlockStack($name);
        if ($stream->nextIf(\WPML\Core\Twig\Token::BLOCK_END_TYPE)) {
            $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
            if ($token = $stream->nextIf(\WPML\Core\Twig\Token::NAME_TYPE)) {
                $value = $token->getValue();
                if ($value != $name) {
                    throw new \WPML\Core\Twig\Error\SyntaxError(\sprintf('Expected endblock for block "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
                }
            }
        } else {
            $body = new \WPML\Core\Twig\Node\Node([new \WPML\Core\Twig\Node\PrintNode($this->parser->getExpressionParser()->parseExpression(), $lineno)]);
        }
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $block->setNode('body', $body);
        $this->parser->popBlockStack();
        $this->parser->popLocalScope();
        return new \WPML\Core\Twig\Node\BlockReferenceNode($name, $lineno, $this->getTag());
    }
    public function decideBlockEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endblock');
    }
    public function getTag()
    {
        return 'block';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\BlockTokenParser', 'WPML\\Core\\Twig_TokenParser_Block');
