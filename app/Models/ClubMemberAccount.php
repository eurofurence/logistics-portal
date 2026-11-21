<?php

namespace App\Models;

use Database\Factories\ClubMemberAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ClubMemberAccount extends Authenticatable
{
    /** @use HasFactory<ClubMemberAccountFactory> */
    use HasFactory;

    public const GUARD = 'club_member';

    public const SESSION_VERSION = 'club_member_session_version';

    protected $fillable = ['login_id', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'session_version' => 'integer'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class, 'club_member_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ClubMemberToken::class);
    }
}
