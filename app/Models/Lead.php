<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'source', 'message', 'handled', 'ip'];

    protected function casts(): array
    {
        return ['handled' => 'boolean'];
    }
}
