<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    use HasFactory;

    protected $table = 'bancos';

    protected $fillable = [
        'nombre',
    ];

    public function cheques()
    {
        return $this->hasMany(Cheque::class, 'banco_id');
    }
}
