<?php

namespace App\Exceptions;

class BlizzardServiceException extends ApiException
{
    protected $message = 'There was an error while using Blizzard Services.';
}
