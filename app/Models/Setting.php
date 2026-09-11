<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'is_encrypted', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
