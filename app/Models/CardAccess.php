<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardAccess extends Model
{
    public $timestamps = false;
    protected $table = 'card_access';
    protected $fillable = [
        'pengguna',
        'UID',
        'kelas',
        'jurusan'
    ];
}
