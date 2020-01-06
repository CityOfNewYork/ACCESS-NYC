<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\Error;

/**
 * Exception thrown when an error occurs during template loading.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LoaderError extends \WPML\Core\Twig\Error\Error
{
}
\class_alias('WPML\\Core\\Twig\\Error\\LoaderError', 'WPML\\Core\\Twig_Error_Loader');
