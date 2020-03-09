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
use WPML\Core\Twig\Node\Expression\AssignNameExpression;
use WPML\Core\Twig\Node\Expression\ConstantExpression;
use WPML\Core\Twig\Node\Expression\GetAttrExpression;
use WPML\Core\Twig\Node\Expression\NameExpression;
use WPML\Core\Twig\Node\ForNode;
use WPML\Core\Twig\Token;
use WPML\Core\Twig\TokenStream;
/**
 * Loops over each item of a sequence.
 *
 *   <ul>
 *    {% for user in users %}
 *      <li>{{ user.username|e }}</li>
 *    {% endfor %}
 *   </ul>
 *
 * @final
 */
class ForTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $stream->expect(\WPML\Core\Twig\Token::OPERATOR_TYPE, 'in');
        $seq = $this->parser->getExpressionParser()->parseExpression();
        $ifexpr = null;
        if ($stream->nextIf(\WPML\Core\Twig\Token::NAME_TYPE, 'if')) {
            $ifexpr = $this->parser->getExpressionParser()->parseExpression();
        }
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideForFork']);
        if ('else' == $stream->next()->getValue()) {
            $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
            $else = $this->parser->subparse([$this, 'decideForEnd'], \true);
        } else {
            $else = null;
        }
        $stream->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        if (\count($targets) > 1) {
            $keyTarget = $targets->getNode(0);
            $keyTarget = new \WPML\Core\Twig\Node\Expression\AssignNameExpression($keyTarget->getAttribute('name'), $keyTarget->getTemplateLine());
            $valueTarget = $targets->getNode(1);
            $valueTarget = new \WPML\Core\Twig\Node\Expression\AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        } else {
            $keyTarget = new \WPML\Core\Twig\Node\Expression\AssignNameExpression('_key', $lineno);
            $valueTarget = $targets->getNode(0);
            $valueTarget = new \WPML\Core\Twig\Node\Expression\AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        }
        if ($ifexpr) {
            $this->checkLoopUsageCondition($stream, $ifexpr);
            $this->checkLoopUsageBody($stream, $body);
        }
        return new \WPML\Core\Twig\Node\ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, $lineno, $this->getTag());
    }
    public function decideForFork(\WPML\Core\Twig\Token $token)
    {
        return $token->test(['else', 'endfor']);
    }
    public function decideForEnd(\WPML\Core\Twig\Token $token)
    {
        return $token->test('endfor');
    }
    // the loop variable cannot be used in the condition
    protected function checkLoopUsageCondition(\WPML\Core\Twig\TokenStream $stream, \WPML\Core\Twig_NodeInterface $node)
    {
        if ($node instanceof \WPML\Core\Twig\Node\Expression\GetAttrExpression && $node->getNode('node') instanceof \WPML\Core\Twig\Node\Expression\NameExpression && 'loop' == $node->getNode('node')->getAttribute('name')) {
            throw new \WPML\Core\Twig\Error\SyntaxError('The "loop" variable cannot be used in a looping condition.', $node->getTemplateLine(), $stream->getSourceContext());
        }
        foreach ($node as $n) {
            if (!$n) {
                continue;
            }
            $this->checkLoopUsageCondition($stream, $n);
        }
    }
    // check usage of non-defined loop-items
    // it does not catch all problems (for instance when a for is included into another or when the variable is used in an include)
    protected function checkLoopUsageBody(\WPML\Core\Twig\TokenStream $stream, \WPML\Core\Twig_NodeInterface $node)
    {
        if ($node instanceof \WPML\Core\Twig\Node\Expression\GetAttrExpression && $node->getNode('node') instanceof \WPML\Core\Twig\Node\Expression\NameExpression && 'loop' == $node->getNode('node')->getAttribute('name')) {
            $attribute = $node->getNode('attribute');
            if ($attribute instanceof \WPML\Core\Twig\Node\Expression\ConstantExpression && \in_array($attribute->getAttribute('value'), ['length', 'revindex0', 'revindex', 'last'])) {
                throw new \WPML\Core\Twig\Error\SyntaxError(\sprintf('The "loop.%s" variable is not defined when looping with a condition.', $attribute->getAttribute('value')), $node->getTemplateLine(), $stream->getSourceContext());
            }
        }
        // should check for parent.loop.XXX usage
        if ($node instanceof \WPML\Core\Twig\Node\ForNode) {
            return;
        }
        foreach ($node as $n) {
            if (!$n) {
                continue;
            }
            $this->checkLoopUsageBody($stream, $n);
        }
    }
    public function getTag()
    {
        return 'for';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\ForTokenParser', 'WPML\\Core\\Twig_TokenParser_For');
