<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CardAccess extends Model
{
    public $timestamps = false;
    protected $table   = 'card_access';

    protected $fillable = [
        'pengguna',
        'UID',
        'kelas',
        'jurusan',
    ];

    // -----------------------------------------------------------------------
    // SCOPES
    // -----------------------------------------------------------------------

    /**
     * Cari berdasarkan UID, normalisasi spasi dan uppercase.
     * ESP32 MFRC522 mengirim UID dengan format "AB CD EF 01" (uppercase, spasi antar byte).
     * Pastikan data di DB juga disimpan dalam format yang sama.
     */
    public function scopeByUid(Builder $query, string $uid): Builder
    {
        $normalized = strtoupper(trim($uid));
        return $query->whereRaw('UPPER(TRIM(UID)) = ?', [$normalized]);
    }

    // -----------------------------------------------------------------------
    // STATIC HELPERS
    // -----------------------------------------------------------------------

    /**
     * Cari kartu berdasarkan UID (case-insensitive, trim).
     */
    public static function findByUid(string $uid): ?self
    {
        $normalized = strtoupper(trim($uid));
        return static::whereRaw('UPPER(TRIM(UID)) = ?', [$normalized])->first();
    }

    // -----------------------------------------------------------------------
    // ACCESSORS
    // -----------------------------------------------------------------------

    /**
     * Selalu simpan UID dalam uppercase tanpa leading/trailing spasi.
     */
    public function setUIDAttribute(string $value): void
    {
        $this->attributes['UID'] = strtoupper(trim($value));
    }
}
