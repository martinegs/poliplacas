<?php

use App\Models\Caja;
use App\Models\Entidad;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public ?Caja $caja = null;

    public $monto = '';
    public $tipo = 'efectivo';
    public $movimiento = 'egreso';
    public $motivo = '';
    public $entidad_id = '';
    public $created_at = '';

    public function mount(?Caja $caja = null)
    {
        if ($caja && $caja->exists) {
            $this->caja = $caja;
            $this->monto = $caja->monto;
            $this->tipo = $caja->tipo;
            $this->movimiento = $caja->movimiento;
            $this->motivo = $caja->motivo;
            $this->entidad_id = $caja->entidad_id;
            $this->created_at = $caja->created_at ? $caja->created_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i');
        } else {
            $this->created_at = now()->format('Y-m-d\TH:i');
        }
    }

    public function rules()
    {
        return [
            'monto' => 'required|numeric|min:0.01',
            'tipo' => 'required|string|in:efectivo,cheque',
            'movimiento' => 'required|string|in:ingreso,egreso',
            'motivo' => 'required|string|max:255',
            'entidad_id' => 'nullable|exists:entidades,id',
            'created_at' => 'required|date|before_or_equal:now',
        ];
    }

    public function messages()
    {
        return [
            'created_at.before_or_equal' => 'La fecha y hora no puede ser posterior a la actual.',
        ];
    }

    #[On('entidad-creada')]
    public function onEntidadCreada($id)
    {
        $this->entidad_id = $id;
    }

    public function save()
    {
        $validated = $this->validate();

        if (empty($validated['entidad_id'])) {
            $validated['entidad_id'] = null;
        }

        if ($this->caja && $this->caja->exists) {
            $this->caja->update($validated);
            session()->flash('success', 'Movimiento de caja actualizado correctamente.');
        } else {
            Caja::create($validated);
            session()->flash('success', 'Movimiento de caja registrado correctamente.');
        }

        return $this->redirectRoute('cajas.index');
    }

    public function render()
    {
        $proveedores = Entidad::orderBy('nombre')->get();
        return $this->view(compact('proveedores'));
    }
};
?>

<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="movimiento" class="form-label">Tipo de Movimiento</label>
                <select wire:model="movimiento" id="movimiento" class="form-select" required>
                    <option value="egreso">Egreso (Salida / Gasto)</option>
                    <option value="ingreso">Ingreso (Entrada)</option>
                </select>
                @error('movimiento') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="tipo" class="form-label">Medio de Pago / Cobro</label>
                <select wire:model="tipo" id="tipo" class="form-select" required>
                    <option value="efectivo">Efectivo</option>
                    <option value="cheque">Cheque</option>
                </select>
                @error('tipo') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="monto" class="form-label">Monto</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" wire:model="monto" id="monto" class="form-control" placeholder="0.00" required>
                </div>
                @error('monto') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="created_at" class="form-label">Fecha y Hora</label>
                <input type="datetime-local" wire:model="created_at" id="created_at" class="form-control" max="{{ now()->format('Y-m-d\TH:i') }}" required>
                @error('created_at') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="motivo" class="form-label">Motivo / Detalle</label>
            <input type="text" wire:model="motivo" id="motivo" class="form-control" placeholder="Ej: Venta de placas, pago de flete, compra de materiales..." required>
            @error('motivo') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="entidad_id" class="form-label">Proveedor / Cliente <span class="text-muted">(Opcional)</span></label>
            <div class="d-flex gap-2">
                <select wire:model="entidad_id" id="entidad_id" class="form-select">
                    <option value="">-- Selecciona una entidad --</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }} ({{ $proveedor->dni_cuit ?? 'Sin CUIT' }})</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#nuevoProveedorModal" title="Nueva Entidad">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus m-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </div>
            @error('entidad_id') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('cajas.index') }}" class="btn btn-link link-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary px-4">
                {{ $caja && $caja->exists ? 'Guardar Cambios' : 'Registrar Movimiento' }}
            </button>
        </div>
    </form>

    <!-- Bootstrap Modal for Creating a New Provider/Entity -->
    <div class="modal fade" id="nuevoProveedorModal" tabindex="-1" aria-labelledby="nuevoProveedorModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevoProveedorModalLabel">Nueva Entidad</h5>
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
        const modalEl = document.getElementById('nuevoProveedorModal');
        const closeBtn = modalEl.querySelector('[data-bs-dismiss="modal"]');
        if (closeBtn) {
            closeBtn.click();
        }
    });

    document.getElementById('nuevoProveedorModal').addEventListener('shown.bs.modal', function () {
        if (window.mapInstance) {
            window.mapInstance.invalidateSize();
        }
    });
</script>
@endscript
