<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $fillable = ['user_id', 'nidn', 'nip', 'phone'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
