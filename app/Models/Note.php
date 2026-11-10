<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Note extends Model
{
    protected $fillable = [
        'title',
        'content',
        'attachment',
        'notarized_hash',
        'tx_hash',
        'wallet_address',
        'notarized_at',
    ];

    protected $casts = [
        'notarized_at' => 'datetime',
    ];
    protected $appends = [
        'current_hash',
        'integrity_status',
    ];

    /**
     * Deterministic 32-byte SHA-256 hex fingerprint (0x...) of title + content + attachment bytes.
     */
    public function getCurrentHashAttribute(): string
    {
        $fileHash = '';
        if ($this->attachment && Storage::disk('public')->exists($this->attachment)) {
            $fileHash = hash('sha256', Storage::disk('public')->get($this->attachment));
        }
        $payload = implode('|', [
            trim((string) $this->title),
            trim((string) $this->content),
            $fileHash,
        ]);
        return '0x' . hash('sha256', $payload);
    }
    /**
     * Returns: 'unnotarized' | 'verified' | 'tampered'
     */
    public function getIntegrityStatusAttribute(): string
    {
        if (!$this->notarized_hash) {
            return 'unnotarized';
        }
        return hash_equals(strtolower($this->notarized_hash), strtolower($this->current_hash))
            ? 'verified'
            : 'tampered';
    }
}
