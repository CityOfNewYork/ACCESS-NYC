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
use WPML\Core\Twig\Node\BodyNode;
use WPML\Core\Twig\Node\MacroNode;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Token;
/**
 * Defines a macro.
 *
 *   {% macro input(name, value, type, size) %}
 *      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
 *   {% endmacro %}
 *
 * @final
 */
class MacroTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(\WPML\Core\Twig\Token::NAME_TYPE)->getValue();
        $arguments = $this->parser->getExpressionParser()->parseArguments(\true, \true);
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $this->parser->pushLocalScope();
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        if ($token = $stream->nextIf(\WPML\Core\Twig\Token::NAME_TYPE)) {
            $value = $token->getValue();
            if ($value != $name) {
                throw new \WPML\Core\Twig\Error\SyntaxError(\sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $this->parser->popLocalScope();
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $this->parser->setMacro($name, new \WPML\Core\Twig\Node\MacroNode($name, new \WPML\Core\Twig\Node\BodyNode([$body]), $arguments, $lineno, $this->getTag()));
        return new \WPML\Core\Twig\Node\Node();
    }
    public function decideBlockEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endmacro');
    }
    public function getTag()
    {
        return 'macro';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\MacroTokenParser', 'WPML\\Core\\Twig_TokenParser_Macro');
