<?php

use App\Models\Entidad;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {

    use WithPagination;
    public $search = '';
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function render()
    {
        $query = Entidad::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nombre', 'ilike', '%' . $this->search . '%')
                  ->orWhere('dni_cuit', 'ilike', '%' . $this->search . '%')
                  ->orWhere('email', 'ilike', '%' . $this->search . '%');
            });
        }

        $entidades = $query->paginate(10);

        return $this->view(compact('entidades'));
    }

};
?>

<div>
    <div class="mb-3">
        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
            placeholder="Buscar por nombre, DNI/CUIT o email...">
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped table-hover">
                <thead>
                    <tr>
                        <th>DNI / CUIT</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entidades as $entidad) {{-- Itera sobre cada registro devuelto por paginate() --}}
                        <tr>
                            <td class="text-muted">{{ $entidad->dni_cuit }}</td> {{-- Muestra el campo dni_cuit --}}

                            <td class="fw-semibold">{{ $entidad->nombre }}</td> {{-- Muestra el campo nombre --}}
                            <td class="text-nowrap">{{ $entidad->telefono }}</td> {{-- Muestra el campo telefono --}}
                            <td class="text-muted">{{ $entidad->email }}</td> {{-- Muestra el campo email --}}
                            <td>{{ $entidad->direccion }}</td> {{-- Muestra el campo direccion --}}
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('entidades.edit', $entidad) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit-2" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <path d="M16 3l5 5L8 21H3v-5L16 3z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <form action="{{ route('entidades.destroy', $entidad) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta entidad?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" title="Eliminar">
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
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach {{-- Cierra el loop --}}
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $entidades->links() }} {{-- Renderiza los botones de paginación (Anterior / Siguiente / números) --}}
    </div>
</div>