<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMemberToken extends Model
{
    public const ACTIVATION = 'activation';

    public const RESET = 'reset';

    public const EMAIL_CHANGE = 'email-change';

    public $timestamps = false;

    protected $fillable = ['purpose', 'digest', 'expires_at', 'current_email', 'destination'];

    protected $hidden = ['digest', 'current_email', 'destination'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'current_email' => 'encrypted',
            'destination' => 'encrypted',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ClubMemberAccount::class, 'club_member_account_id');
    }
}
