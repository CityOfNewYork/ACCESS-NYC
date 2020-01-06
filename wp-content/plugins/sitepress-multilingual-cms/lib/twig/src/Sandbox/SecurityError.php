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

use WPML\Core\Twig\Error\Error;
/**
 * Exception thrown when a security error occurs at runtime.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityError extends \WPML\Core\Twig\Error\Error
{
}
\class_alias('WPML\\Core\\Twig\\Sandbox\\SecurityError', 'WPML\\Core\\Twig_Sandbox_SecurityError');
