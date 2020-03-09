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

use WPML\Core\Twig\NodeVisitor\OptimizerNodeVisitor;
/**
 * @final
 */
class OptimizerExtension extends \WPML\Core\Twig\Extension\AbstractExtension
{
    protected $optimizers;
    public function __construct($optimizers = -1)
    {
        $this->optimizers = $optimizers;
    }
    public function getNodeVisitors()
    {
        return [new \WPML\Core\Twig\NodeVisitor\OptimizerNodeVisitor($this->optimizers)];
    }
    public function getName()
    {
        return 'optimizer';
    }
}
\class_alias('WPML\\Core\\Twig\\Extension\\OptimizerExtension', 'WPML\\Core\\Twig_Extension_Optimizer');
