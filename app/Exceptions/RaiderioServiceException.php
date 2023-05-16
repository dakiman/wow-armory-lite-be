<?php

namespace App\Exceptions;

class RaiderioServiceException extends ApiException
{
    protected $message = 'There was an error while using RaiderIO services.';
}
