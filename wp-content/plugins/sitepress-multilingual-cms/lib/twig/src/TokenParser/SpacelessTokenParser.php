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

use WPML\Core\Twig\Node\SpacelessNode;
use WPML\Core\Twig\Token;
/**
 * Remove whitespaces between HTML tags.
 *
 *   {% spaceless %}
 *      <div>
 *          <strong>foo</strong>
 *      </div>
 *   {% endspaceless %}
 *   {# output will be <div><strong>foo</strong></div> #}
 *
 * @final
 */
class SpacelessTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $this->parser->getStream()->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], \true);
        $this->parser->getStream()->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\SpacelessNode($body, $lineno, $this->getTag());
    }
    public function decideSpacelessEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endspaceless');
    }
    public function getTag()
    {
        return 'spaceless';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\SpacelessTokenParser', 'WPML\\Core\\Twig_TokenParser_Spaceless');
