<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Profiler\NodeVisitor;

use WPML\Core\Twig\Environment;
use WPML\Core\Twig\Node\BlockNode;
use WPML\Core\Twig\Node\BodyNode;
use WPML\Core\Twig\Node\MacroNode;
use WPML\Core\Twig\Node\ModuleNode;
use WPML\Core\Twig\Node\Node;
use WPML\Core\Twig\NodeVisitor\AbstractNodeVisitor;
use WPML\Core\Twig\Profiler\Node\EnterProfileNode;
use WPML\Core\Twig\Profiler\Node\LeaveProfileNode;
use WPML\Core\Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class ProfilerNodeVisitor extends \WPML\Core\Twig\NodeVisitor\AbstractNodeVisitor
{
    private $extensionName;
    public function __construct($extensionName)
    {
        $this->extensionName = $extensionName;
    }
    protected function doEnterNode(\WPML\Core\Twig\Node\Node $node, \WPML\Core\Twig\Environment $env)
    {
        return $node;
    }
    protected function doLeaveNode(\WPML\Core\Twig\Node\Node $node, \WPML\Core\Twig\Environment $env)
    {
        if ($node instanceof \WPML\Core\Twig\Node\ModuleNode) {
            $varName = $this->getVarName();
            $node->setNode('display_start', new \WPML\Core\Twig\Node\Node([new \WPML\Core\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \WPML\Core\Twig\Profiler\Profile::TEMPLATE, $node->getTemplateName(), $varName), $node->getNode('display_start')]));
            $node->setNode('display_end', new \WPML\Core\Twig\Node\Node([new \WPML\Core\Twig\Profiler\Node\LeaveProfileNode($varName), $node->getNode('display_end')]));
        } elseif ($node instanceof \WPML\Core\Twig\Node\BlockNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \WPML\Core\Twig\Node\BodyNode([new \WPML\Core\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \WPML\Core\Twig\Profiler\Profile::BLOCK, $node->getAttribute('name'), $varName), $node->getNode('body'), new \WPML\Core\Twig\Profiler\Node\LeaveProfileNode($varName)]));
        } elseif ($node instanceof \WPML\Core\Twig\Node\MacroNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \WPML\Core\Twig\Node\BodyNode([new \WPML\Core\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \WPML\Core\Twig\Profiler\Profile::MACRO, $node->getAttribute('name'), $varName), $node->getNode('body'), new \WPML\Core\Twig\Profiler\Node\LeaveProfileNode($varName)]));
        }
        return $node;
    }
    private function getVarName()
    {
        return \sprintf('__internal_%s', \hash('sha256', $this->extensionName));
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('WPML\\Core\\Twig\\Profiler\\NodeVisitor\\ProfilerNodeVisitor', 'WPML\\Core\\Twig_Profiler_NodeVisitor_Profiler');
