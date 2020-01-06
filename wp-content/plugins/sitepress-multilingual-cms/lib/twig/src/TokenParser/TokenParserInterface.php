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

use WPML\Core\Twig\Error\SyntaxError;
use WPML\Core\Twig\Parser;
use WPML\Core\Twig\Token;
/**
 * Interface implemented by token parsers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface TokenParserInterface
{
    /**
     * Sets the parser associated with this token parser.
     */
    public function setParser(\WPML\Core\Twig\Parser $parser);
    /**
     * Parses a token and returns a node.
     *
     * @return \Twig_NodeInterface
     *
     * @throws SyntaxError
     */
    public function parse(\WPML\Core\Twig\Token $token);
    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag();
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\TokenParserInterface', 'WPML\\Core\\Twig_TokenParserInterface');
// Ensure that the aliased name is loaded to keep BC for classes implementing the typehint with the old aliased name.
\class_exists('WPML\\Core\\Twig\\Token');
\class_exists('WPML\\Core\\Twig\\Parser');
