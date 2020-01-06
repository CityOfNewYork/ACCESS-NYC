<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\NodeVisitor;

use WPML\Core\Twig\Environment;
use WPML\Core\Twig\Node\AutoEscapeNode;
use WPML\Core\Twig\Node\BlockNode;
use WPML\Core\Twig\Node\BlockReferenceNode;
use WPML\Core\Twig\Node\DoNode;
use WPML\Core\Twig\Node\Expression\ConditionalExpression;
use WPML\Core\Twig\Node\Expression\ConstantExpression;
use WPML\Core\Twig\Node\Expression\FilterExpression;
use WPML\Core\Twig\Node\Expression\InlinePrint;
use WPML\Core\Twig\Node\ImportNode;
use WPML\Core\Twig\Node\ModuleNode;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Node\PrintNode;
use WPML\Core\Twig\NodeTraverser;
/**
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class EscaperNodeVisitor extends \WPML\Core\Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $statusStack = [];
    protected $blocks = [];
    protected $safeAnalysis;
    protected $traverser;
    protected $defaultStrategy = \false;
    protected $safeVars = [];
    public function __construct()
    {
        $this->safeAnalysis = new \WPML\Core\Twig\NodeVisitor\SafeAnalysisNodeVisitor();
    }
    protected function doEnterNode(\WPML\Core\Twig\Node\Node $node, \WPML\Core\Twig\Environment $env)
    {
        if ($node instanceof \WPML\Core\Twig\Node\ModuleNode) {
            if ($env->hasExtension('WPML\\Core\\Twig\\Extension\\EscaperExtension') && ($defaultStrategy = $env->getExtension('WPML\\Core\\Twig\\Extension\\EscaperExtension')->getDefaultStrategy($node->getTemplateName()))) {
                $this->defaultStrategy = $defaultStrategy;
            }
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \WPML\Core\Twig\Node\AutoEscapeNode) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof \WPML\Core\Twig\Node\BlockNode) {
            $this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping($env);
        } elseif ($node instanceof \WPML\Core\Twig\Node\ImportNode) {
            $this->safeVars[] = $node->getNode('var')->getAttribute('name');
        }
        return $node;
    }
    protected function doLeaveNode(\WPML\Core\Twig\Node\Node $node, \WPML\Core\Twig\Environment $env)
    {
        if ($node instanceof \WPML\Core\Twig\Node\ModuleNode) {
            $this->defaultStrategy = \false;
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \WPML\Core\Twig\Node\Expression\FilterExpression) {
            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof \WPML\Core\Twig\Node\PrintNode && \false !== ($type = $this->needEscaping($env))) {
            $expression = $node->getNode('expr');
            if ($expression instanceof \WPML\Core\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expression, $env, $type)) {
                return new \WPML\Core\Twig\Node\DoNode($this->unwrapConditional($expression, $env, $type), $expression->getTemplateLine());
            }
            return $this->escapePrintNode($node, $env, $type);
        }
        if ($node instanceof \WPML\Core\Twig\Node\AutoEscapeNode || $node instanceof \WPML\Core\Twig\Node\BlockNode) {
            \array_pop($this->statusStack);
        } elseif ($node instanceof \WPML\Core\Twig\Node\BlockReferenceNode) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping($env);
        }
        return $node;
    }
    private function shouldUnwrapConditional(\WPML\Core\Twig\Node\Expression\ConditionalExpression $expression, \WPML\Core\Twig\Environment $env, $type)
    {
        $expr2Safe = $this->isSafeFor($type, $expression->getNode('expr2'), $env);
        $expr3Safe = $this->isSafeFor($type, $expression->getNode('expr3'), $env);
        return $expr2Safe !== $expr3Safe;
    }
    private function unwrapConditional(\WPML\Core\Twig\Node\Expression\ConditionalExpression $expression, \WPML\Core\Twig\Environment $env, $type)
    {
        // convert "echo a ? b : c" to "a ? echo b : echo c" recursively
        $expr2 = $expression->getNode('expr2');
        if ($expr2 instanceof \WPML\Core\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expr2, $env, $type)) {
            $expr2 = $this->unwrapConditional($expr2, $env, $type);
        } else {
            $expr2 = $this->escapeInlinePrintNode(new \WPML\Core\Twig\Node\Expression\InlinePrint($expr2, $expr2->getTemplateLine()), $env, $type);
        }
        $expr3 = $expression->getNode('expr3');
        if ($expr3 instanceof \WPML\Core\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expr3, $env, $type)) {
            $expr3 = $this->unwrapConditional($expr3, $env, $type);
        } else {
            $expr3 = $this->escapeInlinePrintNode(new \WPML\Core\Twig\Node\Expression\InlinePrint($expr3, $expr3->getTemplateLine()), $env, $type);
        }
        return new \WPML\Core\Twig\Node\Expression\ConditionalExpression($expression->getNode('expr1'), $expr2, $expr3, $expression->getTemplateLine());
    }
    private function escapeInlinePrintNode(\WPML\Core\Twig\Node\Expression\InlinePrint $node, \WPML\Core\Twig\Environment $env, $type)
    {
        $expression = $node->getNode('node');
        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }
        return new \WPML\Core\Twig\Node\Expression\InlinePrint($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }
    protected function escapePrintNode(\WPML\Core\Twig\Node\PrintNode $node, \WPML\Core\Twig\Environment $env, $type)
    {
        if (\false === $type) {
            return $node;
        }
        $expression = $node->getNode('expr');
        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }
        $class = \get_class($node);
        return new $class($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }
    protected function preEscapeFilterNode(\WPML\Core\Twig\Node\Expression\FilterExpression $filter, \WPML\Core\Twig\Environment $env)
    {
        $name = $filter->getNode('filter')->getAttribute('value');
        $type = $env->getFilter($name)->getPreEscape();
        if (null === $type) {
            return $filter;
        }
        $node = $filter->getNode('node');
        if ($this->isSafeFor($type, $node, $env)) {
            return $filter;
        }
        $filter->setNode('node', $this->getEscaperFilter($type, $node));
        return $filter;
    }
    protected function isSafeFor($type, \WPML\Core\Twig_NodeInterface $expression, $env)
    {
        $safe = $this->safeAnalysis->getSafe($expression);
        if (null === $safe) {
            if (null === $this->traverser) {
                $this->traverser = new \WPML\Core\Twig\NodeTraverser($env, [$this->safeAnalysis]);
            }
            $this->safeAnalysis->setSafeVars($this->safeVars);
            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }
        return \in_array($type, $safe) || \in_array('all', $safe);
    }
    protected function needEscaping(\WPML\Core\Twig\Environment $env)
    {
        if (\count($this->statusStack)) {
            return $this->statusStack[\count($this->statusStack) - 1];
        }
        return $this->defaultStrategy ? $this->defaultStrategy : \false;
    }
    protected function getEscaperFilter($type, \WPML\Core\Twig_NodeInterface $node)
    {
        $line = $node->getTemplateLine();
        $name = new \WPML\Core\Twig\Node\Expression\ConstantExpression('escape', $line);
        $args = new \WPML\Core\Twig\Node\Node([new \WPML\Core\Twig\Node\Expression\ConstantExpression((string) $type, $line), new \WPML\Core\Twig\Node\Expression\ConstantExpression(null, $line), new \WPML\Core\Twig\Node\Expression\ConstantExpression(\true, $line)]);
        return new \WPML\Core\Twig\Node\Expression\FilterExpression($node, $name, $args, $line);
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('WPML\\Core\\Twig\\NodeVisitor\\EscaperNodeVisitor', 'WPML\\Core\\Twig_NodeVisitor_Escaper');
