<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Sandbox;

/**
 * Exception thrown when a not allowed tag is used in a template.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class SecurityNotAllowedTagError extends \WPML\Core\Twig\Sandbox\SecurityError
{
    private $tagName;
    public function __construct($message, $tagName, $lineno = -1, $filename = null, \Exception $previous = null)
    {
        parent::__construct($message, $lineno, $filename, $previous);
        $this->tagName = $tagName;
    }
    public function getTagName()
    {
        return $this->tagName;
    }
}
\class_alias('WPML\\Core\\Twig\\Sandbox\\SecurityNotAllowedTagError', 'WPML\\Core\\Twig_Sandbox_SecurityNotAllowedTagError');
