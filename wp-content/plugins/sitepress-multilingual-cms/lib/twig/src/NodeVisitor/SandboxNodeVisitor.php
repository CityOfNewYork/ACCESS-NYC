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
use WPML\Core\Twig\Node\CheckSecurityNode;
use WPML\Core\Twig\Node\CheckToStringNode;
use WPML\Core\Twig\Node\Expression\Binary\ConcatBinary;
use WPML\Core\Twig\Node\Expression\Binary\RangeBinary;
use WPML\Core\Twig\Node\Expression\FilterExpression;
use WPML\Core\Twig\Node\Expression\FunctionExpression;
use WPML\Core\Twig\Node\Expression\GetAttrExpression;
use WPML\Core\Twig\Node\Expression\NameExpression;
use WPML\Core\Twig\Node\ModuleNode;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\Node\PrintNode;
use WPML\Core\Twig\Node\SetNode;
/**
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxNodeVisitor extends \WPML\Core\Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $inAModule = \false;
    protected $tags;
    protected $filters;
    protected $functions;
    private $needsToStringWrap = \false;
    protected function doEnterNode(\WPML\Core\Twig\Node\Node $node, \WPML\Core\Twig\Environment $env)
    {
        if ($node instanceof \WPML\Core\Twig\Node\ModuleNode) {
            $this->inAModule = \true;
            $this->tags = [];
            $this->filters = [];
            $this->functions = [];
            return $node;
        } elseif ($this->inAModule) {
            // look for tags
            if ($node->getNodeTag() && !isset($this->tags[$node->getNodeTag()])) {
                $this->tags[$node->getNodeTag()] = $node;
            }
            // look for filters
            if ($node instanceof \WPML\Core\Twig\Node\Expression\FilterExpression && !isset($this->filters[$node->getNode('filter')->getAttribute('value')])) {
                $this->filters[$node->getNode('filter')->getAttribute('value')] = $node;
            }
            // look for functions
            if ($node instanceof \WPML\Core\Twig\Node\Expression\FunctionExpression && !isset($this->functions[$node->getAttribute('name')])) {
                $this->functions[$node->getAttribute('name')] = $node;
            }
            // the .. operator is equivalent to the range() function
            if ($node instanceof \WPML\Core\Twig\Node\Expression\Binary\RangeBinary && !isset($this->functions['range'])) {
                $this->functions['range'] = $node;
            }
            if ($node instanceof \WPML\Core\Twig\Node\PrintNode) {
                $this->needsToStringWrap = \true;
                $this->wrapNode($node, 'expr');
            }
            if ($node instanceof \WPML\Core\Twig\Node\SetNode && !$node->getAttribute('capture')) {
                $this->needsToStringWrap = \true;
            }
            // wrap outer nodes that can implicitly call __toString()
            if ($this->needsToStringWrap) {
                if ($node instanceof \WPML\Core\Twig\Node\Expression\Binary\ConcatBinary) {
                    $this->wrapNode($node, 'left');
                    $this->wrapNode($node, 'right');
                }
                if ($node instanceof \WPML\Core\Twig\Node\Expression\FilterExpression) {
                    $this->wrapNode($node, 'node');
                    $this->wrapArrayNode($node, 'arguments');
                }
                if ($node instanceof \WPML\Core\Twig\Node\Expression\FunctionExpression) {
                    $this->wrapArrayNode($node, 'arguments');
                }
            }
        }
        return $node;
    }
    protected function doLeaveNode(\WPML\Core\Twig\Node\Node $node, \WPML\Core\Twig\Environment $env)
    {
        if ($node instanceof \WPML\Core\Twig\Node\ModuleNode) {
            $this->inAModule = \false;
            $node->getNode('constructor_end')->setNode('_security_check', new \WPML\Core\Twig\Node\Node([new \WPML\Core\Twig\Node\CheckSecurityNode($this->filters, $this->tags, $this->functions), $node->getNode('display_start')]));
        } elseif ($this->inAModule) {
            if ($node instanceof \WPML\Core\Twig\Node\PrintNode || $node instanceof \WPML\Core\Twig\Node\SetNode) {
                $this->needsToStringWrap = \false;
            }
        }
        return $node;
    }
    private function wrapNode(\WPML\Core\Twig\Node\Node $node, $name)
    {
        $expr = $node->getNode($name);
        if ($expr instanceof \WPML\Core\Twig\Node\Expression\NameExpression || $expr instanceof \WPML\Core\Twig\Node\Expression\GetAttrExpression) {
            $node->setNode($name, new \WPML\Core\Twig\Node\CheckToStringNode($expr));
        }
    }
    private function wrapArrayNode(\WPML\Core\Twig\Node\Node $node, $name)
    {
        $args = $node->getNode($name);
        foreach ($args as $name => $_) {
            $this->wrapNode($args, $name);
        }
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('WPML\\Core\\Twig\\NodeVisitor\\SandboxNodeVisitor', 'WPML\\Core\\Twig_NodeVisitor_Sandbox');
