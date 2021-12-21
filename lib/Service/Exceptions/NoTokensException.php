<?php

namespace OCA\SocialLogin\Service\Exceptions;

class NoTokensException extends TokensException
{
    protected $message = 'Could not find tokens for the provided uid.';

}