<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'monto',
        'tipo',
        'movimiento',
        'motivo',
        'entidad_id',
        'created_at',
    ];

    /**
     * Get the entity/provider associated with the transaction.
     */
    public function proveedor()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    /**
     * Get the cheque associated with this cashing record.
     */
    public function chequeCobro()
    {
        return $this->hasOne(Cheque::class, 'caja_cobro_id');
    }
}
