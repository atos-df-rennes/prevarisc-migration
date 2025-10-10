<?php

namespace App\Linter\TwigCsFixer;

use TwigCsFixer\Rules\AbstractRule;
use TwigCsFixer\Token\Token;
use TwigCsFixer\Token\Tokens;

class NoDumpRule extends AbstractRule
{
    protected function process(int $tokenIndex, Tokens $tokens): void
    {
        $token = $tokens->get($tokenIndex);

        if ($token->isMatching(Token::FUNCTION_NAME_TYPE, 'dump')
            || $token->isMatching(Token::BLOCK_NAME_TYPE, 'dump')
        ) {
            $this->addError('dump() is not allowed.', $token);
        }
    }
}
