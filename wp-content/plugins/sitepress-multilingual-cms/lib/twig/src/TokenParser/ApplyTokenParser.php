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

use WPML\Core\Twig\Node\Expression\TempNameExpression;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Node\PrintNode;
use WPML\Core\Twig\Node\SetNode;
use WPML\Core\Twig\Token;
/**
 * Applies filters on a section of a template.
 *
 *   {% apply upper %}
 *      This text becomes uppercase
 *   {% endapplys %}
 */
final class ApplyTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $name = $this->parser->getVarName();
        $ref = new \WPML\Core\Twig\Node\Expression\TempNameExpression($name, $lineno);
        $ref->setAttribute('always_defined', \true);
        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideApplyEnd'], \true);
        $this->parser->getStream()->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\Node([new \WPML\Core\Twig\Node\SetNode(\true, $ref, $body, $lineno, $this->getTag()), new \WPML\Core\Twig\Node\PrintNode($filter, $lineno, $this->getTag())]);
    }
    public function decideApplyEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endapply');
    }
    public function getTag()
    {
        return 'apply';
    }
}
