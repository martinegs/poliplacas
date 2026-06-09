<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    protected $table = 'cheques';

    protected $fillable = [
        'numero',
        'banco',
        'monto',
        'fecha_cobro',
        'entidad_id',
        'estado',
        'observaciones',
        'receptor_id',
        'fecha_salida',
        'caja_id',
        'caja_egreso_id',
        'caja_cobro_id',
    ];

    protected $casts = [
        'fecha_cobro' => 'date',
        'fecha_salida' => 'date',
        'monto' => 'decimal:2',
    ];

    /**
     * Get the entity/provider who delivered the check.
     */
    public function entregadoPor()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    /**
     * Get the entity/provider who received the check (egreso).
     */
    public function receptor()
    {
        return $this->belongsTo(Entidad::class, 'receptor_id');
    }

    /**
     * Get the Caja entry associated with the receipt (ingreso) of the check.
     */
    public function cajaIngreso()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    /**
     * Get the Caja entry associated with the delivery (egreso) of the check.
     */
    public function cajaEgreso()
    {
        return $this->belongsTo(Caja::class, 'caja_egreso_id');
    }

    /**
     * Get the Caja entry associated with the cashing (ingreso pesos) of the check.
     */
    public function cajaCobro()
    {
        return $this->belongsTo(Caja::class, 'caja_cobro_id');
    }
}
