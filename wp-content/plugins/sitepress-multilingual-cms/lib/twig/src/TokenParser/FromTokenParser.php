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
use WPML\Core\Twig\Node\Expression\AssignNameExpression;
use WPML\Core\Twig\Node\ImportNode;
use WPML\Core\Twig\Token;
/**
 * Imports macros.
 *
 *   {% from 'forms.html' import forms %}
 *
 * @final
 */
class FromTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(\WPML\Core\Twig\Token::NAME_TYPE, 'import');
        $targets = [];
        do {
            $name = $stream->expect(\WPML\Core\Twig\Token::NAME_TYPE)->getValue();
            $alias = $name;
            if ($stream->nextIf('as')) {
                $alias = $stream->expect(\WPML\Core\Twig\Token::NAME_TYPE)->getValue();
            }
            $targets[$name] = $alias;
            if (!$stream->nextIf(\WPML\Core\Twig\Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        } while (\true);
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $var = new \WPML\Core\Twig\Node\Expression\AssignNameExpression($this->parser->getVarName(), $token->getLine());
        $node = new \WPML\Core\Twig\Node\ImportNode($macro, $var, $token->getLine(), $this->getTag());
        foreach ($targets as $name => $alias) {
            if ($this->parser->isReservedMacroName($name)) {
                throw new \WPML\Core\Twig\Error\SyntaxError(\sprintf('"%s" cannot be an imported macro as it is a reserved keyword.', $name), $token->getLine(), $stream->getSourceContext());
            }
            $this->parser->addImportedSymbol('function', $alias, 'get' . $name, $var);
        }
        return $node;
    }
    public function getTag()
    {
        return 'from';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\FromTokenParser', 'WPML\\Core\\Twig_TokenParser_From');
