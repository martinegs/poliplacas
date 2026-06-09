<?php

use App\Models\Cheque;
use App\Models\Caja;
use App\Models\Entidad;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $estado = '';
    public $entidad_id = '';

    // Modal state for Cash or Transfer (safer data representation)
    public $selectedChequeId = null;
    public $selectedChequeNumero = '';
    public $selectedChequeMonto = 0;
    
    public $modalActionType = 'cobrar'; // 'cobrar' or 'transferir'
    public $modalReceptorId = '';
    public $modalFechaSalida = '';
    public $modalFechaCobroReal = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'fecha_desde' => ['except' => ''],
        'fecha_hasta' => ['except' => ''],
        'estado' => ['except' => ''],
        'entidad_id' => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void { $this->resetPage(); }
    public function updatingFechaHasta(): void { $this->resetPage(); }
    public function updatingEstado(): void { $this->resetPage(); }
    public function updatingEntidadId(): void { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'fecha_desde', 'fecha_hasta', 'estado', 'entidad_id']);
        $this->resetPage();
    }

    public function openActionModal($chequeId)
    {
        $cheque = Cheque::findOrFail($chequeId);
        $this->selectedChequeId = $cheque->id;
        $this->selectedChequeNumero = $cheque->numero;
        $this->selectedChequeMonto = $cheque->monto;
        $this->modalActionType = 'cobrar';
        $this->modalReceptorId = '';
        $this->modalFechaSalida = now()->format('Y-m-d');
        $this->modalFechaCobroReal = now()->format('Y-m-d');
    }

    public function processAction()
    {
        if (!$this->selectedChequeId) return;

        $cheque = Cheque::findOrFail($this->selectedChequeId);

        if ($this->modalActionType === 'transferir') {
            $this->validate([
                'modalReceptorId' => 'required|exists:entidades,id',
                'modalFechaSalida' => 'required|date|before_or_equal:today',
            ], [
                'modalReceptorId.required' => 'El receptor es obligatorio.',
                'modalFechaSalida.required' => 'La fecha de entrega es obligatoria.',
                'modalFechaSalida.before_or_equal' => 'La fecha no puede ser posterior a hoy.',
            ]);
        } else {
            $this->validate([
                'modalFechaCobroReal' => 'required|date|before_or_equal:today',
            ], [
                'modalFechaCobroReal.required' => 'La fecha de cobro es obligatoria.',
                'modalFechaCobroReal.before_or_equal' => 'La fecha no puede ser posterior a hoy.',
            ]);
        }

        \DB::transaction(function() use ($cheque) {
            if ($this->modalActionType === 'transferir') {
                // 1. Mark check as entregado
                // 2. Create/update egreso in Caja of type cheque
                $cajaEgresoData = [
                    'monto' => $cheque->monto,
                    'tipo' => 'cheque',
                    'movimiento' => 'egreso',
                    'motivo' => "Entrega/Endoso de Cheque #{$cheque->numero} ({$cheque->banco})",
                    'entidad_id' => $this->modalReceptorId,
                    'created_at' => $this->modalFechaSalida,
                ];

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

                // Delete the cash ingress if it was previously cashed
                if ($cheque->caja_cobro_id) {
                    Caja::destroy($cheque->caja_cobro_id);
                    $cheque->caja_cobro_id = null;
                }

                $cheque->estado = 'entregado';
                $cheque->receptor_id = $this->modalReceptorId;
                $cheque->fecha_salida = $this->modalFechaSalida;

            } else { // cobrar
                // 1. Mark check as cobrado
                // 2. Egress the check from Caja (tipo = cheque, egreso)
                // 3. Ingress cash to Caja (tipo = efectivo, ingreso), motive = "Cobro de cheque #... a cuenta propia"

                $cajaEgresoData = [
                    'monto' => $cheque->monto,
                    'tipo' => 'cheque',
                    'movimiento' => 'egreso',
                    'motivo' => "Egreso de cheque #{$cheque->numero} por cobro",
                    'entidad_id' => $cheque->entidad_id ?: null,
                    'created_at' => $this->modalFechaCobroReal,
                ];

                $cajaIngresoData = [
                    'monto' => $cheque->monto,
                    'tipo' => 'efectivo',
                    'movimiento' => 'ingreso',
                    'motivo' => "Cobro de cheque #{$cheque->numero} a cuenta propia",
                    'entidad_id' => $cheque->entidad_id ?: null,
                    'created_at' => $this->modalFechaCobroReal,
                ];

                // Save or update Egreso (cheque)
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

                // Save or update Ingreso (efectivo)
                if ($cheque->caja_cobro_id) {
                    $cajaCobro = Caja::find($cheque->caja_cobro_id);
                    if ($cajaCobro) {
                        $cajaCobro->update($cajaIngresoData);
                    } else {
                        $cajaCobro = Caja::create($cajaIngresoData);
                        $cheque->caja_cobro_id = $cajaCobro->id;
                    }
                } else {
                    $cajaCobro = Caja::create($cajaIngresoData);
                    $cheque->caja_cobro_id = $cajaCobro->id;
                }

                $cheque->estado = 'cobrado';
                $cheque->receptor_id = null;
                $cheque->fecha_salida = null;
            }

            $cheque->save();
        });

        session()->flash('success', 'Cheque #' . $cheque->numero . ($this->modalActionType === 'transferir' ? ' transferido' : ' cobrado') . ' correctamente.');
        $this->dispatch('close-action-modal');
        $this->reset(['selectedChequeId', 'selectedChequeNumero', 'selectedChequeMonto', 'modalReceptorId']);
    }

    public function deleteCheque($id)
    {
        $cheque = Cheque::findOrFail($id);

        \DB::transaction(function() use ($cheque) {
            // Clean up Caja entries
            if ($cheque->caja_id) {
                Caja::destroy($cheque->caja_id);
            }
            if ($cheque->caja_egreso_id) {
                Caja::destroy($cheque->caja_egreso_id);
            }
            if ($cheque->caja_cobro_id) {
                Caja::destroy($cheque->caja_cobro_id);
            }
            $cheque->delete();
        });

        session()->flash('success', 'Cheque y sus movimientos de caja asociados eliminados.');
    }

    public function render()
    {
        $entidades = Entidad::orderBy('nombre')->get();

        $query = Cheque::query()->with(['entregadoPor', 'receptor']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('numero', 'ilike', '%' . $this->search . '%')
                  ->orWhere('banco', 'ilike', '%' . $this->search . '%')
                  ->orWhere('observaciones', 'ilike', '%' . $this->search . '%')
                  ->orWhereHas('entregadoPor', function($eq) {
                      $eq->where('nombre', 'ilike', '%' . $this->search . '%');
                  })
                  ->orWhereHas('receptor', function($rq) {
                      $rq->where('nombre', 'ilike', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->fecha_desde) {
            $query->whereDate('fecha_cobro', '>=', $this->fecha_desde);
        }

        if ($this->fecha_hasta) {
            $query->whereDate('fecha_cobro', '<=', $this->fecha_hasta);
        }

        if ($this->estado) {
            $query->where('estado', $this->estado);
        }

        if ($this->entidad_id) {
            $query->where(function($q) {
                $q->where('entidad_id', $this->entidad_id)
                  ->orWhere('receptor_id', $this->entidad_id);
            });
        }

        // Calculate Totals based on filtered query
        $totalPendientes = (clone $query)->where('estado', 'pendiente')->sum('monto');
        $totalCobrados = (clone $query)->where('estado', 'cobrado')->sum('monto');
        $totalEntregados = (clone $query)->where('estado', 'entregado')->sum('monto');
        $totalRechazados = (clone $query)->where('estado', 'rechazado')->sum('monto');

        $cheques = $query->orderBy('fecha_cobro', 'asc')->paginate(10);

        return $this->view(compact('cheques', 'entidades', 'totalPendientes', 'totalCobrados', 'totalEntregados', 'totalRechazados'));
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-3 mb-3" role="alert">
            <div class="d-flex">
                <div class="me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                       <path d="M5 12l5 5l10 -10"></path>
                    </svg>
                </div>
                <div>
                    {{ session('success') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Interactive Filters Form -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Buscar cheque</label>
                    <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                        placeholder="Número, banco u observaciones...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Entidad relacionada</label>
                    <select class="form-select" wire:model.live="entidad_id">
                        <option value="">Todas</option>
                        @foreach($entidades as $ent)
                            <option value="{{ $ent->id }}">{{ $ent->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select" wire:model.live="estado">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="cobrado">Cobrado</option>
                        <option value="entregado">Entregado (Egreso)</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Fecha Cobro Desde</label>
                    <input type="date" class="form-control" wire:model.live="fecha_desde">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha Cobro Hasta</label>
                    <input type="date" class="form-control" wire:model.live="fecha_hasta">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1" wire:click="resetFilters" title="Limpiar Filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash m-0" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                           <line x1="4" y1="7" x2="20" y2="7"></line>
                           <line x1="10" y1="11" x2="10" y2="17"></line>
                           <line x1="14" y1="11" x2="14" y2="17"></line>
                           <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                           <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                        </svg>
                        <span>Limpiar Filtros</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Totals Cards -->
    <div class="row row-cards mb-3">
        <!-- Pending -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>
                                   <path d="M12 7v5l3 3"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total Pendientes</div>
                            <div class="text-secondary font-weight-bold h3 mb-0">${{ number_format($totalPendientes, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Cashed -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checkbox" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M9 11l3 3l8 -8"></path>
                                   <path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total Cobrados</div>
                            <div class="text-secondary font-weight-bold h3 mb-0">${{ number_format($totalCobrados, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delivered -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-up-right" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M17 7l-10 10"></path>
                                   <path d="M8 7l9 0l0 9"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total Entregados</div>
                            <div class="text-secondary font-weight-bold h3 mb-0">${{ number_format($totalEntregados, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Rejected -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M12 9v4"></path>
                                   <path d="M12 17h.01"></path>
                                   <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total Rechazados</div>
                            <div class="text-secondary font-weight-bold h3 mb-0">${{ number_format($totalRechazados, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Checks Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
                <thead>
                    <tr>
                        <th>Fecha de Cobro</th>
                        <th>Número</th>
                        <th>Banco</th>
                        <th>Entregado Por (Ingreso)</th>
                        <th>Entregado A (Egreso)</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Estado</th>
                        <th>Observaciones</th>
                        <th class="w-1">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cheques as $cheque)
                        <tr wire:key="cheque-row-{{ $cheque->id }}">
                            <td class="text-secondary">
                                {{ $cheque->fecha_cobro ? $cheque->fecha_cobro->format('d/m/Y') : '-' }}
                            </td>
                            <td class="font-weight-bold">{{ $cheque->numero }}</td>
                            <td class="text-secondary">{{ $cheque->banco }}</td>
                            <td>
                                @if ($cheque->entregadoPor)
                                    <span class="font-weight-medium">{{ $cheque->entregadoPor->nombre }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($cheque->estado === 'entregado' && $cheque->receptor)
                                    <span class="font-weight-medium text-info">{{ $cheque->receptor->nombre }}</span>
                                    <div class="text-muted small">{{ $cheque->fecha_salida ? $cheque->fecha_salida->format('d/m/Y') : '' }}</div>
                                @elseif ($cheque->estado === 'cobrado')
                                    <span class="badge bg-success-lt d-inline-flex align-items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-wallet" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"></path>
                                           <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4z"></path>
                                        </svg>
                                        <span>Caja Propia</span>
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end font-weight-bold text-secondary">
                                ${{ number_format($cheque->monto, 2, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if ($cheque->estado === 'pendiente')
                                    <span class="badge bg-warning-lt">Pendiente</span>
                                @elseif ($cheque->estado === 'cobrado')
                                    <span class="badge bg-success-lt">Cobrado</span>
                                @elseif ($cheque->estado === 'entregado')
                                    <span class="badge bg-info-lt">Entregado</span>
                                @elseif ($cheque->estado === 'rechazado')
                                    <span class="badge bg-danger-lt">Rechazado</span>
                                @endif
                            </td>
                            <td class="text-secondary small">
                                {{ Str::limit($cheque->observaciones, 50) }}
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    @if ($cheque->estado === 'pendiente')
                                        <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#actionModal" wire:click="openActionModal({{ $cheque->id }})" title="Cobrar o Transferir">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrows-exchange" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                               <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                               <path d="M7 10h14l-4 -4"></path>
                                               <path d="M17 14h-14l4 4"></path>
                                            </svg>
                                            <span>Cobrar/Transf.</span>
                                        </button>
                                    @endif
                                    <a href="{{ route('cheques.edit', $cheque) }}" class="btn btn-icon btn-outline-primary btn-sm" title="Editar / Entregar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-pencil m-0" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4"></path>
                                           <path d="M13.5 6.5l4 4"></path>
                                        </svg>
                                    </a>
                                    <button type="button" class="btn btn-icon btn-outline-danger btn-sm" wire:click="deleteCheque({{ $cheque->id }})" wire:confirm="¿Estás seguro de que deseas eliminar este cheque y sus movimientos de caja?" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash m-0" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <line x1="4" y1="7" x2="20" y2="7"></line>
                                           <line x1="10" y1="11" x2="10" y2="17"></line>
                                           <line x1="14" y1="11" x2="14" y2="17"></line>
                                           <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                           <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No se encontraron registros de cheques.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $cheques->links() }}
    </div>

    <!-- Modal for Cobrar / Transferir -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <form wire:submit.prevent="processAction" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">
                        @if ($modalActionType === 'cobrar')
                            Cobrar Cheque #{{ $selectedChequeNumero }}
                        @else
                            Transferir Cheque #{{ $selectedChequeNumero }}
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    @if (!$selectedChequeId)
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="text-muted mt-2">Cargando detalles del cheque...</div>
                        </div>
                    @else
                        <!-- Toggle action type -->
                        <div class="mb-3">
                            <label class="form-label">Acción a realizar</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="actionType" value="cobrar" class="form-selectgroup-input" wire:model.live="modalActionType">
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </span>
                                        <span class="form-selectgroup-label-content text-start">
                                            <span class="font-weight-medium d-block">Cobrar</span>
                                            <span class="text-secondary text-wrap small">Depositar o cobrar a cuenta propia.</span>
                                        </span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="actionType" value="transferir" class="form-selectgroup-input" wire:model.live="modalActionType">
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </span>
                                        <span class="form-selectgroup-label-content text-start">
                                            <span class="font-weight-medium d-block">Transferir</span>
                                            <span class="text-secondary text-wrap small">Endosar y entregar a una entidad/proveedor.</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        @if ($modalActionType === 'transferir')
                            <!-- Transfer form -->
                            <div class="mb-3">
                                <label for="modalReceptorId" class="form-label font-weight-bold">Transferir a (Entidad/Proveedor) <span class="text-danger">*</span></label>
                                <select wire:model="modalReceptorId" id="modalReceptorId" class="form-select" required>
                                    <option value="">-- Selecciona el receptor --</option>
                                    @foreach($entidades as $entidad)
                                        <option value="{{ $entidad->id }}">{{ $entidad->nombre }} ({{ $entidad->dni_cuit ?? 'Sin CUIT' }})</option>
                                    @endforeach
                                </select>
                                @error('modalReceptorId') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="modalFechaSalida" class="form-label font-weight-bold">Fecha de Entrega <span class="text-danger">*</span></label>
                                <input type="date" wire:model="modalFechaSalida" id="modalFechaSalida" class="form-control" max="{{ now()->format('Y-m-d') }}" required>
                                @error('modalFechaSalida') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <!-- Cash form -->
                            <div class="mb-3">
                                <label for="modalFechaCobroReal" class="form-label font-weight-bold">Fecha de Cobro <span class="text-danger">*</span></label>
                                <input type="date" wire:model="modalFechaCobroReal" id="modalFechaCobroReal" class="form-control" max="{{ now()->format('Y-m-d') }}" required>
                                @error('modalFechaCobroReal') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
                    @if ($selectedChequeId)
                        <button type="submit" class="btn btn-success">
                            @if ($modalActionType === 'cobrar')
                                Confirmar Cobro
                            @else
                                Confirmar Transferencia
                            @endif
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('close-action-modal', () => {
        const modalEl = document.getElementById('actionModal');
        if (modalEl) {
            const closeBtn = modalEl.querySelector('[data-bs-dismiss="modal"]');
            if (closeBtn) {
                closeBtn.click();
            }
        }
    });
</script>
@endscript
