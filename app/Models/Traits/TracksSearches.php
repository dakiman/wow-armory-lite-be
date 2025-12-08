<?php

namespace App\Models\Traits;

use Carbon\Carbon;

trait TracksSearches
{
    /**
     * Record a search for this entity.
     */
    public function recordSearch(): void
    {
        $this->increment('search_count');
        $this->update(['last_searched_at' => now()]);
    }

    /**
     * Check if the stored data is stale and needs refreshing.
     */
    public function isDataStale(): bool
    {
        // Never fetched
        if (! $this->data_fetched_at) {
            return true;
        }

        // Schema invalidation (config date approach)
        $validAfter = Carbon::parse(config('blizzard.data_valid_after'));
        if ($this->data_fetched_at->lt($validAfter)) {
            return true;
        }

        // TTL-based staleness
        $ttlSeconds = $this->getDataTtlSeconds();
        if ($this->data_fetched_at->addSeconds($ttlSeconds)->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if data exists and is not stale.
     */
    public function hasFreshData(): bool
    {
        return $this->data !== null && ! $this->isDataStale();
    }

    /**
     * Mark as pending fetch.
     */
    public function markAsPending(): void
    {
        $this->update([
            'fetch_status' => 'pending',
            'fetch_queued_at' => now(),
        ]);
    }

    /**
     * Mark as currently fetching.
     */
    public function markAsFetching(): void
    {
        $this->update(['fetch_status' => 'fetching']);
    }

    /**
     * Mark fetch as complete with data.
     *
     * @param  array<string, mixed>  $data
     */
    public function markAsComplete(array $data): void
    {
        $this->update([
            'data' => $data,
            'data_fetched_at' => now(),
            'fetch_status' => 'complete',
        ]);
    }

    /**
     * Mark fetch as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['fetch_status' => 'failed']);
    }

    /**
     * Reset to idle status.
     */
    public function resetFetchStatus(): void
    {
        $this->update([
            'fetch_status' => 'idle',
            'fetch_queued_at' => null,
        ]);
    }

    /**
     * Check if a fetch is currently in progress.
     */
    public function isFetchInProgress(): bool
    {
        return in_array($this->fetch_status, ['pending', 'fetching']);
    }

    /**
     * Get the TTL in seconds for this entity's data.
     */
    abstract protected function getDataTtlSeconds(): int;
}
