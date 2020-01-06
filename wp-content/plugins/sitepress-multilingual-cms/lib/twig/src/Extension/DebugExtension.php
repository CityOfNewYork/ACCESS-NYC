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

use WPML\Core\Twig\TwigFunction;
/**
 * @final
 */
class DebugExtension extends \WPML\Core\Twig\Extension\AbstractExtension
{
    public function getFunctions()
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = \extension_loaded('xdebug') && (\false === \ini_get('xdebug.overload_var_dump') || \ini_get('xdebug.overload_var_dump')) && (\false === \ini_get('html_errors') || \ini_get('html_errors')) || 'cli' === \PHP_SAPI;
        return [new \WPML\Core\Twig\TwigFunction('dump', 'twig_var_dump', ['is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [], 'needs_context' => \true, 'needs_environment' => \true, 'is_variadic' => \true])];
    }
    public function getName()
    {
        return 'debug';
    }
}
\class_alias('WPML\\Core\\Twig\\Extension\\DebugExtension', 'WPML\\Core\\Twig_Extension_Debug');
namespace WPML\Core;

use WPML\Core\Twig\Environment;
use WPML\Core\Twig\Template;
use WPML\Core\Twig\TemplateWrapper;
function twig_var_dump(\WPML\Core\Twig\Environment $env, $context, array $vars = [])
{
    if (!$env->isDebug()) {
        return;
    }
    \ob_start();
    if (!$vars) {
        $vars = [];
        foreach ($context as $key => $value) {
            if (!$value instanceof \WPML\Core\Twig\Template && !$value instanceof \WPML\Core\Twig\TemplateWrapper) {
                $vars[$key] = $value;
            }
        }
        \var_dump($vars);
    } else {
        foreach ($vars as $var) {
            \var_dump($var);
        }
    }
    return \ob_get_clean();
}
