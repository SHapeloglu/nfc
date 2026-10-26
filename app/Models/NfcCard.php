<?php

namespace App\Models;

use Database\Factories\NfcCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfcCard extends Model
{
    /** @use HasFactory<NfcCardFactory> */
    use HasFactory;

    protected $fillable = ['label', 'status', 'tag_uid', 'written_url', 'written_at', 'verified_at', 'delivered_at'];

    protected function casts(): array
    {
        return [
            'written_at' => 'datetime',
            'verified_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function linkProject(): BelongsTo
    {
        return $this->belongsTo(LinkProject::class);
    }
}
