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

use WPML\Core\Twig\Profiler\NodeVisitor\ProfilerNodeVisitor;
use WPML\Core\Twig\Profiler\Profile;
class ProfilerExtension extends \WPML\Core\Twig\Extension\AbstractExtension
{
    private $actives = [];
    public function __construct(\WPML\Core\Twig\Profiler\Profile $profile)
    {
        $this->actives[] = $profile;
    }
    public function enter(\WPML\Core\Twig\Profiler\Profile $profile)
    {
        $this->actives[0]->addProfile($profile);
        \array_unshift($this->actives, $profile);
    }
    public function leave(\WPML\Core\Twig\Profiler\Profile $profile)
    {
        $profile->leave();
        \array_shift($this->actives);
        if (1 === \count($this->actives)) {
            $this->actives[0]->leave();
        }
    }
    public function getNodeVisitors()
    {
        return [new \WPML\Core\Twig\Profiler\NodeVisitor\ProfilerNodeVisitor(\get_class($this))];
    }
    public function getName()
    {
        return 'profiler';
    }
}
\class_alias('WPML\\Core\\Twig\\Extension\\ProfilerExtension', 'WPML\\Core\\Twig_Extension_Profiler');
