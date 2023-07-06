<?php

namespace App\Services\Disputable;

use App\Models\Dispute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait DisputableTrait
{
    /**
     * Report the model
     *
     * @param \Illuminate\Database\Eloquent\Model $disputer
     * @param string $flags
     * @param string $note
     */
    public function dispute(Model $disputer, string $flags, string $title, string $note): Dispute
    {
        ($dispute = new Dispute())->forceFill([
            'owner_id' => $disputer->getKey(),
            'owner_type' => $disputer->getMorphClass(),
            'disputable_id' => $this->getKey(),
            'disputable_type' => $this->getMorphClass(),
            'flags' => $flags,
            'title' => $title,
            'note' => $note,
        ])->save();

        return $dispute;
    }

    /**
     * Get related disputes
     *
     * @return Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function disputes(): MorphMany
    {
        return $this->morphMany(Dispute::class, 'disputable');
    }
}
