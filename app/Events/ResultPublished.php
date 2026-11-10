<?php

namespace App\Events;

use App\Models\Result;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an attempt has been scored and its Result saved.
 *
 * ShouldDispatchAfterCommit: if it is fired inside a transaction, listeners only run
 * once that transaction commits — so nobody reacts to a result that could still be rolled back.
 */
class ResultPublished implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Result $result) {}
}
