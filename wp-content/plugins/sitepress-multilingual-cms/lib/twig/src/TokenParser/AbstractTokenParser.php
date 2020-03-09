<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPML\Core\Twig\TokenParser;

use WPML\Core\Twig\Parser;
/**
 * Base class for all token parsers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractTokenParser implements \WPML\Core\Twig\TokenParser\TokenParserInterface
{
    /**
     * @var Parser
     */
    protected $parser;
    public function setParser(\WPML\Core\Twig\Parser $parser)
    {
        $this->parser = $parser;
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\AbstractTokenParser', 'WPML\\Core\\Twig_TokenParser');
