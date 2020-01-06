<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Extension;

use WPML\Core\Twig\NodeVisitor\SandboxNodeVisitor;
use WPML\Core\Twig\Sandbox\SecurityPolicyInterface;
use WPML\Core\Twig\TokenParser\SandboxTokenParser;
/**
 * @final
 */
class SandboxExtension extends \WPML\Core\Twig\Extension\AbstractExtension
{
    protected $sandboxedGlobally;
    protected $sandboxed;
    protected $policy;
    public function __construct(\WPML\Core\Twig\Sandbox\SecurityPolicyInterface $policy, $sandboxed = \false)
    {
        $this->policy = $policy;
        $this->sandboxedGlobally = $sandboxed;
    }
    public function getTokenParsers()
    {
        return [new \WPML\Core\Twig\TokenParser\SandboxTokenParser()];
    }
    public function getNodeVisitors()
    {
        return [new \WPML\Core\Twig\NodeVisitor\SandboxNodeVisitor()];
    }
    public function enableSandbox()
    {
        $this->sandboxed = \true;
    }
    public function disableSandbox()
    {
        $this->sandboxed = \false;
    }
    public function isSandboxed()
    {
        return $this->sandboxedGlobally || $this->sandboxed;
    }
    public function isSandboxedGlobally()
    {
        return $this->sandboxedGlobally;
    }
    public function setSecurityPolicy(\WPML\Core\Twig\Sandbox\SecurityPolicyInterface $policy)
    {
        $this->policy = $policy;
    }
    public function getSecurityPolicy()
    {
        return $this->policy;
    }
    public function checkSecurity($tags, $filters, $functions)
    {
        if ($this->isSandboxed()) {
            $this->policy->checkSecurity($tags, $filters, $functions);
        }
    }
    public function checkMethodAllowed($obj, $method)
    {
        if ($this->isSandboxed()) {
            $this->policy->checkMethodAllowed($obj, $method);
        }
    }
    public function checkPropertyAllowed($obj, $method)
    {
        if ($this->isSandboxed()) {
            $this->policy->checkPropertyAllowed($obj, $method);
        }
    }
    public function ensureToStringAllowed($obj)
    {
        if ($this->isSandboxed() && \is_object($obj) && \method_exists($obj, '__toString')) {
            $this->policy->checkMethodAllowed($obj, '__toString');
        }
        return $obj;
    }
    public function getName()
    {
        return 'sandbox';
    }
}
\class_alias('WPML\\Core\\Twig\\Extension\\SandboxExtension', 'WPML\\Core\\Twig_Extension_Sandbox');
