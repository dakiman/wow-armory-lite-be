<?php

namespace App\Exceptions;

class RateLimitException extends ApiException
{
    protected $message = 'Rate limit exceeded. Please try again later.';

    protected ?int $retryAfter;

    public function __construct(string $message = null, ?int $retryAfter = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message ?? $this->message, null, 429);
    }

    /**
     * Get the number of seconds until the rate limit resets.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
