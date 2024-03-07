<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'personal_team'
    ];

    protected $casts = [
        'persontal_team' => 'boolean'
    ];

    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function searches() : HasMany
    {
        return $this->hasMany(Search::class);
    }

    public function savedSearches() : HasMany
    {
        return $this->hasMany(Search::class)->whereNotNull('saved_at');
    }

    public function previousSearches() : HasMany
    {
        return $this->hasMany(Search::class)->whereNull('saved_at');
    }

    /**
     * Purge all of the team's resources.
     *
     * @return void
     */
    public function purge()
    {
        $this->owner()->where('current_team_id', $this->id)
                ->update(['current_team_id' => null]);

        // $this->users()->where('current_team_id', $this->id)
        //         ->update(['current_team_id' => null]);

        // $this->users()->detach();

        $this->delete();
    }
}
