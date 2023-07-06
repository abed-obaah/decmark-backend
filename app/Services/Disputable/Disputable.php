<?php

namespace App\Services\Disputable;

use App\Models\Dispute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Disputable
{
    /**
     * Report the model
     *
     * @param string $flags
     * @param string $note
     * @return \App\Models\Disputable
     */
    public function dispute(Model $disputer, string $flags, string $title, string $note): Dispute;

    /**
     * Get related disputes
     *
     * @return Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function disputes(): MorphMany;
}
