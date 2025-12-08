<?php

namespace App\Models;

use App\Models\Traits\TracksSearches;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guild extends Model
{
    use HasFactory;
    use TracksSearches;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'region',
        'realm',
        'name',
        'is_classic',
        'search_count',
        'last_searched_at',
        'data',
        'data_fetched_at',
        'fetch_status',
        'fetch_queued_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_classic' => 'boolean',
        'search_count' => 'integer',
        'last_searched_at' => 'datetime',
        'data' => 'array',
        'data_fetched_at' => 'datetime',
        'fetch_queued_at' => 'datetime',
    ];

    /**
     * Find or create a guild by its unique identifiers.
     */
    public static function findOrCreateByIdentifiers(
        string $region,
        string $realm,
        string $name,
        bool $isClassic = false
    ): self {
        return self::firstOrCreate([
            'region' => $region,
            'realm' => $realm,
            'name' => $name,
            'is_classic' => $isClassic,
        ]);
    }

    /**
     * Scope for most searched guilds.
     */
    public function scopeMostSearched(Builder $query, int $limit = 5): Builder
    {
        return $query
            ->whereNotNull('data')
            ->orderByDesc('search_count')
            ->limit($limit);
    }

    /**
     * Scope for recently searched guilds.
     */
    public function scopeRecentlySearched(Builder $query, int $limit = 5): Builder
    {
        return $query
            ->whereNotNull('data')
            ->whereNotNull('last_searched_at')
            ->orderByDesc('last_searched_at')
            ->limit($limit);
    }

    /**
     * Get the TTL in seconds for guild data.
     */
    protected function getDataTtlSeconds(): int
    {
        return (int) config('blizzard.guild_min_seconds_update', 3600);
    }

    /**
     * Get summary data for popular listings.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'region' => $this->region,
            'realm' => $this->realm,
            'name' => $this->name,
            'is_classic' => $this->is_classic,
            'search_count' => $this->search_count,
            'last_searched_at' => $this->last_searched_at?->toIso8601String(),
            'basic' => $this->data['basic'] ?? null,
        ];
    }
}
