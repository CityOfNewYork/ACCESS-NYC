<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Profiler;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class Profile implements \IteratorAggregate, \Serializable
{
    const ROOT = 'ROOT';
    const BLOCK = 'block';
    const TEMPLATE = 'template';
    const MACRO = 'macro';
    private $template;
    private $name;
    private $type;
    private $starts = [];
    private $ends = [];
    private $profiles = [];
    public function __construct($template = 'main', $type = self::ROOT, $name = 'main')
    {
        $this->template = $template;
        $this->type = $type;
        $this->name = 0 === \strpos($name, '__internal_') ? 'INTERNAL' : $name;
        $this->enter();
    }
    public function getTemplate()
    {
        return $this->template;
    }
    public function getType()
    {
        return $this->type;
    }
    public function getName()
    {
        return $this->name;
    }
    public function isRoot()
    {
        return self::ROOT === $this->type;
    }
    public function isTemplate()
    {
        return self::TEMPLATE === $this->type;
    }
    public function isBlock()
    {
        return self::BLOCK === $this->type;
    }
    public function isMacro()
    {
        return self::MACRO === $this->type;
    }
    public function getProfiles()
    {
        return $this->profiles;
    }
    public function addProfile(self $profile)
    {
        $this->profiles[] = $profile;
    }
    /**
     * Returns the duration in microseconds.
     *
     * @return float
     */
    public function getDuration()
    {
        if ($this->isRoot() && $this->profiles) {
            // for the root node with children, duration is the sum of all child durations
            $duration = 0;
            foreach ($this->profiles as $profile) {
                $duration += $profile->getDuration();
            }
            return $duration;
        }
        return isset($this->ends['wt']) && isset($this->starts['wt']) ? $this->ends['wt'] - $this->starts['wt'] : 0;
    }
    /**
     * Returns the memory usage in bytes.
     *
     * @return int
     */
    public function getMemoryUsage()
    {
        return isset($this->ends['mu']) && isset($this->starts['mu']) ? $this->ends['mu'] - $this->starts['mu'] : 0;
    }
    /**
     * Returns the peak memory usage in bytes.
     *
     * @return int
     */
    public function getPeakMemoryUsage()
    {
        return isset($this->ends['pmu']) && isset($this->starts['pmu']) ? $this->ends['pmu'] - $this->starts['pmu'] : 0;
    }
    /**
     * Starts the profiling.
     */
    public function enter()
    {
        $this->starts = ['wt' => \microtime(\true), 'mu' => \memory_get_usage(), 'pmu' => \memory_get_peak_usage()];
    }
    /**
     * Stops the profiling.
     */
    public function leave()
    {
        $this->ends = ['wt' => \microtime(\true), 'mu' => \memory_get_usage(), 'pmu' => \memory_get_peak_usage()];
    }
    public function reset()
    {
        $this->starts = $this->ends = $this->profiles = [];
        $this->enter();
    }
    public function getIterator()
    {
        return new \ArrayIterator($this->profiles);
    }
    public function serialize()
    {
        return \serialize($this->__serialize());
    }
    public function unserialize($data)
    {
        $this->__unserialize(\unserialize($data));
    }
    /**
     * @internal
	 * @phpcs:disable PHPCompatibility.FunctionNameRestrictions.NewMagicMethods.__serializeFound
     */
    public function __serialize()
    {
        return [$this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles];
    }
    /**
     * @internal
	 * @phpcs:disable PHPCompatibility.FunctionNameRestrictions.NewMagicMethods.__unserializeFound
	 */
    public function __unserialize(array $data)
    {
        list($this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles) = $data;
    }
}
\class_alias('WPML\\Core\\Twig\\Profiler\\Profile', 'WPML\\Core\\Twig_Profiler_Profile');
