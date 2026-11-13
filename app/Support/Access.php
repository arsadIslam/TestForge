<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * What one student can access right now — built by AccessService::accessFor() with ONE query
 * and then reused for a whole page of tests (no query per test).
 */
final class Access
{
    /**
     * @param  CarbonInterface|null  $allAccessUntil  end of the current All-Access pass, or null if none
     * @param  array<int, CarbonInterface|null>  $seriesUntil  series id => access end (null = lifetime)
     */
    public function __construct(
        public readonly ?CarbonInterface $allAccessUntil = null,
        public readonly array $seriesUntil = [],
    ) {}

    public function hasAllAccess(): bool
    {
        return $this->allAccessUntil !== null;
    }

    /**
     * Free tests (no series) are always accessible.
     */
    public function canAccessSeries(?int $seriesId): bool
    {
        return $seriesId === null || $this->hasAllAccess() || array_key_exists($seriesId, $this->seriesUntil);
    }

    public function ownsSeries(int $seriesId): bool
    {
        return array_key_exists($seriesId, $this->seriesUntil);
    }

    public function seriesUntil(int $seriesId): ?CarbonInterface
    {
        return $this->seriesUntil[$seriesId] ?? null;
    }
}
