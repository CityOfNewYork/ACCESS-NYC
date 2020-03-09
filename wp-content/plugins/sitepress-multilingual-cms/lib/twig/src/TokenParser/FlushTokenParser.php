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

use WPML\Core\Twig\Node\FlushNode;
use WPML\Core\Twig\Token;
/**
 * Flushes the output to the client.
 *
 * @see flush()
 *
 * @final
 */
class FlushTokenParser extends \WPML\Core\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WPML\Core\Twig\Token $token)
    {
        $this->parser->getStream()->expect(\WPML\Core\Twig\Token::BLOCK_END_TYPE);
        return new \WPML\Core\Twig\Node\FlushNode($token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'flush';
    }
}
\class_alias('WPML\\Core\\Twig\\TokenParser\\FlushTokenParser', 'WPML\\Core\\Twig_TokenParser_Flush');
