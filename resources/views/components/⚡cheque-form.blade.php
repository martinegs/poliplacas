<?php

use App\Models\Cheque;
use App\Models\Caja;
use App\Models\Entidad;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public ?Cheque $cheque = null;

    public $numero = '';
    public $banco = '';
    public $monto = '';
    public $fecha_cobro = '';
    public $entidad_id = '';
    public $estado = 'pendiente';
    public $observaciones = '';

    // Egress / Delivery fields
    public $receptor_id = '';
    public $fecha_salida = '';

    // Track which field is target for entity creation modal
    public $targetField = 'entidad_id';

    public function mount(?Cheque $cheque = null)
    {
        if ($cheque && $cheque->exists) {
            $this->cheque = $cheque;
            $this->numero = $cheque->numero;
            $this->banco = $cheque->banco;
            $this->monto = $cheque->monto;
            $this->fecha_cobro = $cheque->fecha_cobro ? $cheque->fecha_cobro->format('Y-m-d') : '';
            $this->entidad_id = $cheque->entidad_id ?: '';
            $this->estado = $cheque->estado;
            $this->observaciones = $cheque->observaciones;
            $this->receptor_id = $cheque->receptor_id ?: '';
            $this->fecha_salida = $cheque->fecha_salida ? $cheque->fecha_salida->format('Y-m-d') : '';
        } else {
            $this->fecha_cobro = now()->format('Y-m-d');
        }
    }

    public function rules()
    {
        return [
            'numero' => 'required|string|max:255',
            'banco' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'fecha_cobro' => 'required|date',
            'entidad_id' => 'nullable|exists:entidades,id',
            'estado' => 'required|string|in:pendiente,cobrado,entregado,rechazado',
            'observaciones' => 'nullable|string|max:1000',
            'receptor_id' => 'required_if:estado,entregado|nullable|exists:entidades,id',
            'fecha_salida' => 'required_if:estado,entregado|nullable|date|before_or_equal:today',
        ];
    }

    public function messages()
    {
        return [
            'receptor_id.required_if' => 'El receptor es obligatorio cuando el cheque se marca como Entregado.',
            'fecha_salida.required_if' => 'La fecha de entrega es obligatoria cuando el cheque se marca como Entregado.',
            'fecha_salida.before_or_equal' => 'La fecha de entrega no puede ser posterior a la actual.',
        ];
    }

    #[On('entidad-creada')]
    public function onEntidadCreada($id)
    {
        if ($this->targetField === 'receptor_id') {
            $this->receptor_id = $id;
        } else {
            $this->entidad_id = $id;
        }
    }

    public function save()
    {
        if (empty($this->entidad_id)) {
            $this->entidad_id = null;
        }
        if (empty($this->receptor_id)) {
            $this->receptor_id = null;
        }

        $validated = $this->validate();

        // 1. Manage Caja entry for the receipt (Ingreso)
        $cajaIngresoData = [
            'monto' => $this->monto,
            'tipo' => 'cheque',
            'movimiento' => 'ingreso',
            'motivo' => "Recepción de Cheque #{$this->numero} ({$this->banco})",
            'entidad_id' => $this->entidad_id ?: null,
            'created_at' => $this->fecha_cobro,
        ];

        // 2. Manage Caja entry for the delivery (Egreso) if applicable
        $cajaEgresoData = null;
        if ($this->estado === 'entregado') {
            $cajaEgresoData = [
                'monto' => $this->monto,
                'tipo' => 'cheque',
                'movimiento' => 'egreso',
                'motivo' => "Entrega/Endoso de Cheque #{$this->numero} ({$this->banco})",
                'entidad_id' => $this->receptor_id,
                'created_at' => $this->fecha_salida ?: now()->format('Y-m-d'),
            ];
        } elseif ($this->estado === 'cobrado') {
            $cajaEgresoData = [
                'monto' => $this->monto,
                'tipo' => 'cheque',
                'movimiento' => 'egreso',
                'motivo' => "Egreso de cheque #{$this->numero} por cobro",
                'entidad_id' => $this->entidad_id ?: null,
                'created_at' => $this->fecha_cobro,
            ];
        }

        // 3. Manage Caja entry for the cash ingress (Ingreso pesos) if cashed
        $cajaCobroData = null;
        if ($this->estado === 'cobrado') {
            $cajaCobroData = [
                'monto' => $this->monto,
                'tipo' => 'efectivo',
                'movimiento' => 'ingreso',
                'motivo' => "Cobro de cheque #{$this->numero} a cuenta propia",
                'entidad_id' => $this->entidad_id ?: null,
                'created_at' => $this->fecha_cobro,
            ];
        }

        // 4. Save Cheque & Caja entries inside DB Transaction
        \DB::transaction(function() use ($cajaIngresoData, $cajaEgresoData, $cajaCobroData) {
            $isNew = !($this->cheque && $this->cheque->exists);
            $cheque = $isNew ? new Cheque() : $this->cheque;

            // Handle Caja Ingreso
            if ($cheque->caja_id) {
                $cajaIngreso = Caja::find($cheque->caja_id);
                if ($cajaIngreso) {
                    $cajaIngreso->update($cajaIngresoData);
                } else {
                    $cajaIngreso = Caja::create($cajaIngresoData);
                    $cheque->caja_id = $cajaIngreso->id;
                }
            } else {
                $cajaIngreso = Caja::create($cajaIngresoData);
                $cheque->caja_id = $cajaIngreso->id;
            }

            // Handle Caja Egreso (Delivery or Cobro Egreso)
            if ($this->estado === 'entregado' || $this->estado === 'cobrado') {
                if ($cheque->caja_egreso_id) {
                    $cajaEgreso = Caja::find($cheque->caja_egreso_id);
                    if ($cajaEgreso) {
                        $cajaEgreso->update($cajaEgresoData);
                    } else {
                        $cajaEgreso = Caja::create($cajaEgresoData);
                        $cheque->caja_egreso_id = $cajaEgreso->id;
                    }
                } else {
                    $cajaEgreso = Caja::create($cajaEgresoData);
                    $cheque->caja_egreso_id = $cajaEgreso->id;
                }
            } else {
                if ($cheque->caja_egreso_id) {
                    Caja::destroy($cheque->caja_egreso_id);
                    $cheque->caja_egreso_id = null;
                }
            }

            // Handle Caja Cobro (Ingreso pesos)
            if ($this->estado === 'cobrado') {
                if ($cheque->caja_cobro_id) {
                    $cajaCobro = Caja::find($cheque->caja_cobro_id);
                    if ($cajaCobro) {
                        $cajaCobro->update($cajaCobroData);
                    } else {
                        $cajaCobro = Caja::create($cajaCobroData);
                        $cheque->caja_cobro_id = $cajaCobro->id;
                    }
                } else {
                    $cajaCobro = Caja::create($cajaCobroData);
                    $cheque->caja_cobro_id = $cajaCobro->id;
                }
            } else {
                if ($cheque->caja_cobro_id) {
                    Caja::destroy($cheque->caja_cobro_id);
                    $cheque->caja_cobro_id = null;
                }
            }

            // Save Cheque details
            $cheque->numero = $this->numero;
            $cheque->banco = $this->banco;
            $cheque->monto = $this->monto;
            $cheque->fecha_cobro = $this->fecha_cobro;
            $cheque->entidad_id = $this->entidad_id ?: null;
            $cheque->estado = $this->estado;
            $cheque->observaciones = $this->observaciones;
            $cheque->receptor_id = $this->estado === 'entregado' ? $this->receptor_id : null;
            $cheque->fecha_salida = $this->estado === 'entregado' ? $this->fecha_salida : null;
            $cheque->save();
        });

        session()->flash('success', $this->cheque && $this->cheque->exists ? 'Cheque actualizado correctamente.' : 'Cheque registrado correctamente.');

        return $this->redirectRoute('cheques.index');
    }

    public function render()
    {
        $entidades = Entidad::orderBy('nombre')->get();
        return $this->view(compact('entidades'));
    }
};
?>

<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="numero" class="form-label">Número de Cheque</label>
                <input type="text" wire:model="numero" id="numero" class="form-control" placeholder="Ej: 12345678" required>
                @error('numero') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="banco" class="form-label">Banco</label>
                <input type="text" wire:model="banco" id="banco" class="form-control" placeholder="Ej: Banco Galicia" required>
                @error('banco') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="monto" class="form-label">Monto</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" wire:model="monto" id="monto" class="form-control" placeholder="0.00" required>
                </div>
                @error('monto') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label for="fecha_cobro" class="form-label">Fecha de Cobro / Vencimiento</label>
                <input type="date" wire:model="fecha_cobro" id="fecha_cobro" class="form-control" required>
                @error('fecha_cobro') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select wire:model.live="estado" id="estado" class="form-select" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="cobrado">Cobrado</option>
                    <option value="entregado">Entregado (Egreso/Proveedor)</option>
                    <option value="rechazado">Rechazado</option>
                </select>
                @error('estado') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Dynamic Egress / Delivery details -->
        @if ($estado === 'entregado')
            <div class="card bg-light border-start border-primary border-3 mb-3 shadow-sm">
                <div class="card-body">
                    <h4 class="card-title text-primary mb-2 d-flex align-items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-up-right" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                           <path d="M17 7l-10 10"></path>
                           <path d="M8 7l9 0l0 9"></path>
                        </svg>
                        <span>Detalles de la Entrega (Egreso de Caja)</span>
                    </h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="receptor_id" class="form-label">Entregado a (Entidad/Proveedor) <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <select wire:model="receptor_id" id="receptor_id" class="form-select" required>
                                    <option value="">-- Selecciona el receptor --</option>
                                    @foreach($entidades as $entidad)
                                        <option value="{{ $entidad->id }}">{{ $entidad->nombre }} ({{ $entidad->dni_cuit ?? 'Sin CUIT' }})</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#nuevaEntidadModal" wire:click="$set('targetField', 'receptor_id')" title="Nueva Entidad">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus m-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                            </div>
                            @error('receptor_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="fecha_salida" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                            <input type="date" wire:model="fecha_salida" id="fecha_salida" class="form-control" max="{{ now()->format('Y-m-d') }}" required>
                            @error('fecha_salida') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mb-3">
            <label for="entidad_id" class="form-label">Recibido de / Entregado por (Cliente) <span class="text-muted">(Opcional)</span></label>
            <div class="d-flex gap-2">
                <select wire:model="entidad_id" id="entidad_id" class="form-select">
                    <option value="">-- Selecciona una entidad --</option>
                    @foreach($entidades as $entidad)
                        <option value="{{ $entidad->id }}">{{ $entidad->nombre }} ({{ $entidad->dni_cuit ?? 'Sin CUIT' }})</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#nuevaEntidadModal" wire:click="$set('targetField', 'entidad_id')" title="Nueva Entidad">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus m-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </div>
            @error('entidad_id') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="observaciones" class="form-label">Observaciones <span class="text-muted">(Opcional)</span></label>
            <textarea wire:model="observaciones" id="observaciones" class="form-control" placeholder="Detalles adicionales, firmante, etc..." rows="3"></textarea>
            @error('observaciones') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('cheques.index') }}" class="btn btn-link link-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary px-4">
                {{ $cheque && $cheque->exists ? 'Guardar Cambios' : 'Registrar Cheque' }}
            </button>
        </div>
    </form>

    <!-- Modal for Creating a New Entity (Unified) -->
    <div class="modal fade" id="nuevaEntidadModal" tabindex="-1" aria-labelledby="nuevaEntidadModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevaEntidadModalLabel">Nueva Entidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    @livewire('⚡entidad-form', ['isModal' => true])
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('entidad-creada', (event) => {
        const modalEl = document.getElementById('nuevaEntidadModal');
        if (modalEl) {
            const closeBtn = modalEl.querySelector('[data-bs-dismiss="modal"]');
            if (closeBtn) {
                closeBtn.click();
            }
        }
    });

    const modalEl = document.getElementById('nuevaEntidadModal');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            if (window.mapInstance) {
                window.mapInstance.invalidateSize();
            }
        });
    }
</script>
@endscript
