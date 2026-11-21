<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMemberChange extends Model
{
    protected $fillable = ['field', 'previous_value', 'new_value', 'staff_id', 'changed_by_member'];

    protected $hidden = ['previous_value', 'new_value'];

    protected function casts(): array
    {
        return [
            'previous_value' => 'encrypted',
            'new_value' => 'encrypted',
            'changed_by_member' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
