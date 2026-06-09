<?php

use App\Models\Caja;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $tipo = '';
    public $movimiento = '';
    public $proveedor_id = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void { $this->resetPage(); }
    public function updatingFechaHasta(): void { $this->resetPage(); }
    public function updatingTipo(): void { $this->resetPage(); }
    public function updatingMovimiento(): void { $this->resetPage(); }
    public function updatingProveedorId(): void { $this->resetPage(); }

    public function search()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'fecha_desde', 'fecha_hasta', 'tipo', 'movimiento', 'proveedor_id']);
        $this->resetPage();
    }

    public function delete($id)
    {
        $caja = Caja::find($id);
        if ($caja) {
            $caja->delete();
            session()->flash('success', 'Movimiento de caja eliminado correctamente.');
        }
    }

    public function render()
    {
        $proveedores = \App\Models\Entidad::orderBy('nombre')->get();

        $query = Caja::query()
            ->with('proveedor');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('motivo', 'ilike', '%' . $this->search . '%')
                  ->orWhere('tipo', 'ilike', '%' . $this->search . '%')
                  ->orWhere('movimiento', 'ilike', '%' . $this->search . '%')
                  ->orWhereHas('proveedor', function($pq) {
                      $pq->where('nombre', 'ilike', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->fecha_desde) {
            $query->whereDate('created_at', '>=', $this->fecha_desde);
        }

        if ($this->fecha_hasta) {
            $query->whereDate('created_at', '<=', $this->fecha_hasta);
        }

        if ($this->tipo) {
            $query->where('tipo', $this->tipo);
        }

        if ($this->movimiento) {
            $query->where('movimiento', $this->movimiento);
        }

        if ($this->proveedor_id) {
            $query->where('entidad_id', $this->proveedor_id);
        }

        $totalIngresos = (clone $query)->where('movimiento', 'ingreso')->sum('monto');
        $totalEgresos = (clone $query)->where('movimiento', 'egreso')->sum('monto');
        $saldoNeto = $totalIngresos - $totalEgresos;

        $cajas = $query->latest()->paginate(10);

        return $this->view(compact('cajas', 'proveedores', 'totalIngresos', 'totalEgresos', 'saldoNeto'));
    }
};
?>

<div>
    <form wire:submit.prevent="search" class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Buscar por motivo</label>
                    <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                        placeholder="Ej: flete, venta, materiales...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Entidad (Proveedor / Cliente)</label>
                    <select class="form-select" wire:model.live="proveedor_id">
                        <option value="">Todos</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo Movimiento</label>
                    <select class="form-select" wire:model.live="movimiento">
                        <option value="">Todos</option>
                        <option value="ingreso">Ingreso</option>
                        <option value="egreso">Egreso</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Medio de Pago</label>
                    <select class="form-select" wire:model.live="tipo">
                        <option value="">Todos</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" wire:model.live="fecha_desde">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha Hasta</label>
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
    </form>

    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trending-up" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M3 17l6 -6l4 4l8 -8"></path>
                                   <path d="M14 7l7 0l0 7"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="text-secondary">Total Ingresos</div>
                            <div class="text-success fw-bold h3 mb-0">+${{ number_format($totalIngresos, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trending-down" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M3 7l6 6l4 -4l8 8"></path>
                                   <path d="M21 10l0 7l-7 0"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="text-secondary">Total Egresos</div>
                            <div class="text-danger fw-bold h3 mb-0">-${{ number_format($totalEgresos, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="{{ $saldoNeto >= 0 ? 'bg-success' : 'bg-danger' }} text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-scale" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M7 20l10 0"></path>
                                   <path d="M6 6l6 -1l6 1"></path>
                                   <path d="M12 3l0 17"></path>
                                   <path d="M9 12l-3 -6l-3 6a3 3 0 0 0 6 0z"></path>
                                   <path d="M21 12l-3 -6l-3 6a3 3 0 0 0 6 0z"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="text-secondary">Saldo Neto</div>
                            <div class="fw-bold h3 mb-0 {{ $saldoNeto >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $saldoNeto >= 0 ? '+' : '' }}${{ number_format($saldoNeto, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Movimiento</th>
                        <th>Motivo</th>
                        <th>Proveedor / Cliente</th>
                        <th>Medio</th>
                        <th>Monto</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cajas as $caja)
                        <tr>
                            <td class="text-muted">{{ $caja->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($caja->movimiento === 'ingreso')
                                    <span class="badge bg-success-lt">Ingreso</span>
                                @else
                                    <span class="badge bg-danger-lt">Egreso</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $caja->motivo }}</td>
                            <td>
                                @if($caja->proveedor)
                                    <span class="badge bg-secondary-lt">{{ $caja->proveedor->nombre }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-blue-lt text-capitalize">{{ $caja->tipo }}</span>
                            </td>
                            <td class="fw-bold {{ $caja->movimiento === 'ingreso' ? 'text-success' : 'text-danger' }}">
                                {{ $caja->movimiento === 'ingreso' ? '+' : '-' }}${{ number_format($caja->monto, 2, ',', '.') }}
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('cajas.edit', $caja) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <path d="M16 3l5 5L8 21H3v-5L16 3z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <button type="button" wire:click="delete({{ $caja->id }})" wire:confirm="¿Estás seguro de que deseas eliminar este movimiento?" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <line x1="4" y1="7" x2="20" y2="7"></line>
                                           <line x1="10" y1="11" x2="10" y2="17"></line>
                                           <line x1="14" y1="11" x2="14" y2="17"></line>
                                           <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                           <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No se encontraron registros de caja.</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $cajas->links() }}
    </div>
</div>
