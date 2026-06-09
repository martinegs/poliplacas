<?php

use Livewire\Component;
use App\Models\Entidad;

new class extends Component
{
    public ?Entidad $entidad = null;
    public $isModal = false;
    public $emitEvent = 'entidad-creada';

    public $dni_cuit = '';
    public $nombre = '';
    public $telefono = '';
    public $email = '';
    public $direccion = '';
    public $coordenadas = '';

    public function mount(?Entidad $entidad = null)
    {
        if ($entidad && $entidad->exists) {
            $this->entidad = $entidad;
            $this->dni_cuit = $entidad->dni_cuit;
            $this->nombre = $entidad->nombre;
            $this->telefono = $entidad->telefono;
            $this->email = $entidad->email;
            $this->direccion = $entidad->direccion;
            $this->coordenadas = $entidad->coordenadas;
        }
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'dni_cuit' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'coordenadas' => 'nullable|string|max:255',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        if ($this->entidad && $this->entidad->exists) {
            $this->entidad->update($validated);
            session()->flash('success', 'Entidad actualizada correctamente.');
        } else {
            $newEntidad = Entidad::create($validated);
            if ($this->isModal) {
                $this->dispatch($this->emitEvent, id: $newEntidad->id);
                return;
            }
            session()->flash('success', 'Entidad creada correctamente.');
        }

        return $this->redirectRoute('entidades.index');
    }
};
?>

<div>
    <form wire:submit.prevent="save">
        <div class="mb-3">
            <label for="dni_cuit" class="form-label">Dni o Cuit</label>
            <input type="text" wire:model="dni_cuit" id="dni_cuit" class="form-control">
        </div>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" wire:model="nombre" id="nombre" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="text" wire:model="telefono" id="telefono" class="form-control">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" wire:model="email" id="email" class="form-control">
        </div>

        <!-- Hidden address field to match database schema -->
        <input type="hidden" id="direccion" wire:model="direccion">

        <div class="row" wire:ignore>
            <div class="col-md-4 mb-3">
                <label for="provincia" class="form-label">Provincia</label>
                <select id="provincia" class="form-select"></select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="departamento" class="form-label">Departamento</label>
                <select id="departamento" class="form-select" disabled></select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="localidad" class="form-label">Localidad</label>
                <select id="localidad" class="form-select" disabled></select>
            </div>
        </div>

        <div class="row" wire:ignore>
            <div class="col-md-6 mb-3">
                <label for="calle" class="form-label">Calle</label>
                <select id="calle" class="form-select" disabled></select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="numero" class="form-label">Número</label>
                <input type="text" id="numero" class="form-control" placeholder="Altura" disabled>
            </div>
            <div class="col-md-3 mb-3">
                <label for="lote_dpto" class="form-label">Lote / Dpto <span class="text-muted">(Opcional)</span></label>
                <input type="text" id="lote_dpto" class="form-control" placeholder="Ej: Lote 5 / 3° B">
            </div>
        </div>

        <input type="hidden" id="coordenadas" wire:model="coordenadas">

        <div class="mb-4" wire:ignore>
            <label class="form-label">Ubicación en Mapa</label>
            <div id="map" style="height: 400px; border-radius: 8px; border: 1px solid #e6e8e9; z-index: 1;"></div>
            <div id="geocoding-status"></div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            @if($isModal)
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
            @else
                <a href="{{ route('entidades.index') }}" class="btn btn-link link-secondary">Cancelar</a>
            @endif
            <button type="submit" class="btn btn-primary px-4">
                {{ $entidad && $entidad->exists ? 'Guardar Cambios' : 'Guardar Entidad' }}
            </button>
        </div>
    </form>
</div>

@script
<script>
    let map, marker;
    
    // Stored coordinates parsing
    const storedCoords = @json($coordenadas);
        let defaultLat = -34.6037;
        let defaultLng = -58.3816;
        let zoom = 13;
        
        if (storedCoords) {
            const parts = storedCoords.split(',');
            const parsedLat = parseFloat(parts[0]);
            const parsedLng = parseFloat(parts[1]);
            if (!isNaN(parsedLat) && !isNaN(parsedLng)) {
                defaultLat = parsedLat;
                defaultLng = parsedLng;
                zoom = 16;
            }
        }

        let tsProvincia, tsDepartamento, tsLocalidad, tsCalle;
        const provinceCentroids = {};
        const departmentCentroids = {};
        const localityCentroids = {};
        let geocodeTimeout;

        // Parse stored address fields
        const storedDireccion = @json($direccion);
        let initialCalle = '';
        let initialNumero = '';
        let initialLoteDpto = '';

        if (storedDireccion) {
            const parts = storedDireccion.split(',');
            const streetPart = parts[0] ? parts[0].trim() : '';
            let streetAndNum = streetPart;
            const matchLote = streetPart.match(/\(([^)]+)\)/);
            if (matchLote) {
                initialLoteDpto = matchLote[1];
                streetAndNum = streetPart.replace(/\([^)]+\)/, '').trim();
            }
            const words = streetAndNum.split(' ');
            if (words.length > 1) {
                const lastWord = words[words.length - 1];
                if (/^\d+$/.test(lastWord) || /^\d+[a-zA-Z]?$/.test(lastWord)) {
                    initialNumero = lastWord;
                    initialCalle = words.slice(0, -1).join(' ');
                } else {
                    initialCalle = streetAndNum;
                }
            } else {
                initialCalle = streetAndNum;
            }
        }

        function initApp() {
            if (typeof window.L === 'undefined' || typeof window.TomSelect === 'undefined') {
                setTimeout(initApp, 50);
                return;
            }

            // 1. Initialize Leaflet Map
            map = L.map('map').setView([defaultLat, defaultLng], zoom);
            window.mapInstance = map;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const customIcon = L.divIcon({
                html: `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-map-pin-filled" width="38" height="38" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #019127; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));">
                         <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                         <path d="M18.364 4.636a9 9 0 0 1 .203 12.519l-.203 .21l-4.243 4.242a3 3 0 0 1 -4.18 .06l-.12 -.11l-4.242 -4.242a9 9 0 0 1 12.783 -12.679zm-6.364 3.364a3 3 0 1 0 0 6 3 3 0 0 0 0 -6z" stroke-width="0" fill="currentColor" />
                       </svg>`,
                className: 'custom-div-icon',
                iconSize: [38, 38],
                iconAnchor: [19, 38]
            });

            marker = L.marker([defaultLat, defaultLng], {
                icon: customIcon,
                draggable: true
            }).addTo(map);

            updateCoordinatesInput(defaultLat, defaultLng);

            // Map Click Event
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                marker.setLatLng([lat, lng]);
                updateCoordinatesInput(lat, lng);
                applyLocationFromCoords(lat, lng);
            });

            // Marker Drag End Event
            marker.on('dragend', function() {
                const position = marker.getLatLng();
                updateCoordinatesInput(position.lat, position.lng);
                applyLocationFromCoords(position.lat, position.lng);
            });

            // 2. Initialize Tom Select Dropdowns
            tsProvincia = new TomSelect('#provincia', {
                placeholder: 'Selecciona una provincia',
                onChange: onProvinciaChange
            });

            tsDepartamento = new TomSelect('#departamento', {
                placeholder: 'Selecciona un departamento',
                onChange: onDepartamentoChange
            });

            tsLocalidad = new TomSelect('#localidad', {
                placeholder: 'Selecciona una localidad',
                onChange: onLocalidadChange
            });

            tsCalle = new TomSelect('#calle', {
                placeholder: 'Busca o escribe la calle...',
                create: true,
                valueField: 'nombre',
                labelField: 'nombre',
                searchField: 'nombre',
                load: function(query, callback) {
                    if (!query.length) return callback();
                    
                    const provId = tsProvincia.getValue();
                    const depId = tsDepartamento.getValue();
                    let url = `https://apis.datos.gob.ar/georef/api/calles?nombre=${encodeURIComponent(query)}&max=20`;
                    if (depId) {
                        url += `&departamento=${depId}`;
                    } else if (provId) {
                        url += `&provincia=${provId}`;
                    }
                    
                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.calles) {
                                callback(data.calles);
                            } else {
                                callback();
                            }
                        })
                        .catch(() => callback());
                },
                onChange: triggerGeocoding
            });

            // 3. Setup event listeners for inputs
            document.getElementById('numero').addEventListener('input', triggerGeocoding);
            document.getElementById('lote_dpto').addEventListener('input', updateHiddenDireccion);

            // 4. Load initial provinces, then apply initial location
            fetch('https://apis.datos.gob.ar/georef/api/provincias?campos=id,nombre,centroide&max=100')
                .then(res => res.json())
                .then(data => {
                    if (data && data.provincias) {
                        const options = data.provincias.map(p => {
                            provinceCentroids[p.id] = p.centroide;
                            return { value: p.id, text: p.nombre };
                        });
                        options.sort((a, b) => a.text.localeCompare(b.text));
                        tsProvincia.addOption(options);

                        // If editing and have coordinates, apply initial address fields hierarchy
                        if (storedCoords) {
                            applyLocationFromCoords(defaultLat, defaultLng, initialCalle, initialNumero, initialLoteDpto);
                        } else {
                            // Locate user if allowed (Create view)
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(function(position) {
                                    const lat = position.coords.latitude;
                                    const lng = position.coords.longitude;
                                    map.setView([lat, lng], 15);
                                    marker.setLatLng([lat, lng]);
                                    updateCoordinatesInput(lat, lng);
                                    applyLocationFromCoords(lat, lng);
                                });
                            }
                        }
                    }
                });
        }

        function onProvinciaChange(val) {
            tsDepartamento.clear();
            tsDepartamento.clearOptions();
            tsDepartamento.disable();

            tsLocalidad.clear();
            tsLocalidad.clearOptions();
            tsLocalidad.disable();

            tsCalle.clear();
            tsCalle.clearOptions();
            tsCalle.disable();

            document.getElementById('numero').value = '';
            document.getElementById('numero').disabled = true;

            if (!val) {
                updateHiddenDireccion();
                return;
            }

            const centroid = provinceCentroids[val];
            if (centroid && centroid.lat && centroid.lon) {
                map.setView([centroid.lat, centroid.lon], 8);
                marker.setLatLng([centroid.lat, centroid.lon]);
                updateCoordinatesInput(centroid.lat, centroid.lon);
            }

            fetch(`https://apis.datos.gob.ar/georef/api/departamentos?provincia=${val}&campos=id,nombre,centroide&max=1000`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.departamentos) {
                        const options = data.departamentos.map(d => {
                            departmentCentroids[d.id] = d.centroide;
                            return { value: d.id, text: d.nombre };
                        });
                        options.sort((a, b) => a.text.localeCompare(b.text));
                        tsDepartamento.clearOptions();
                        tsDepartamento.addOption(options);
                        tsDepartamento.enable();
                    }
                });

            setTimeout(updateHiddenDireccion, 50);
        }

        function onDepartamentoChange(val) {
            tsLocalidad.clear();
            tsLocalidad.clearOptions();
            tsLocalidad.disable();

            tsCalle.clear();
            tsCalle.clearOptions();
            tsCalle.disable();

            document.getElementById('numero').value = '';
            document.getElementById('numero').disabled = true;

            if (!val) {
                updateHiddenDireccion();
                return;
            }

            const centroid = departmentCentroids[val];
            if (centroid && centroid.lat && centroid.lon) {
                map.setView([centroid.lat, centroid.lon], 11);
                marker.setLatLng([centroid.lat, centroid.lon]);
                updateCoordinatesInput(centroid.lat, centroid.lon);
            }

            fetch(`https://apis.datos.gob.ar/georef/api/localidades?departamento=${val}&campos=id,nombre,centroide&max=1000`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.localidades) {
                        const options = data.localidades.map(l => {
                            localityCentroids[l.id] = l.centroide;
                            return { value: l.id, text: l.nombre };
                        });
                        options.sort((a, b) => a.text.localeCompare(b.text));
                        tsLocalidad.clearOptions();
                        tsLocalidad.addOption(options);
                        tsLocalidad.enable();
                    }
                });

            setTimeout(updateHiddenDireccion, 50);
        }

        function onLocalidadChange(val) {
            tsCalle.clear();
            tsCalle.clearOptions();
            tsCalle.disable();

            document.getElementById('numero').value = '';
            document.getElementById('numero').disabled = true;

            if (!val) {
                updateHiddenDireccion();
                return;
            }

            const centroid = localityCentroids[val];
            if (centroid && centroid.lat && centroid.lon) {
                map.setView([centroid.lat, centroid.lon], 14);
                marker.setLatLng([centroid.lat, centroid.lon]);
                updateCoordinatesInput(centroid.lat, centroid.lon);
            }

            tsCalle.enable();
            document.getElementById('numero').disabled = false;

            setTimeout(updateHiddenDireccion, 50);
        }

        function triggerGeocoding() {
            clearTimeout(geocodeTimeout);
            geocodeTimeout = setTimeout(function() {
                const calleVal = tsCalle.getValue();
                const numeroVal = document.getElementById('numero').value.trim();
                const depId = tsDepartamento.getValue();
                const provId = tsProvincia.getValue();

                if (!calleVal) {
                    updateHiddenDireccion();
                    return;
                }

                let query = calleVal;
                if (numeroVal) {
                    query += ' ' + numeroVal;
                }

                const statusDiv = document.getElementById('geocoding-status');
                statusDiv.innerHTML = '<span class="text-muted small"><span class="spinner-border spinner-border-sm me-1" role="status"></span> Buscando altura exacta...</span>';

                let url = `https://apis.datos.gob.ar/georef/api/direcciones?direccion=${encodeURIComponent(query)}&max=1`;
                if (depId) {
                    url += `&departamento=${depId}`;
                } else if (provId) {
                    url += `&provincia=${provId}`;
                }

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.direcciones && data.direcciones.length > 0) {
                            const result = data.direcciones[0];
                            if (result.ubicacion && result.ubicacion.lat && result.ubicacion.lon) {
                                const lat = result.ubicacion.lat;
                                const lng = result.ubicacion.lon;

                                marker.setLatLng([lat, lng]);
                                map.setView([lat, lng], 16);
                                updateCoordinatesInput(lat, lng);

                                statusDiv.innerHTML = `
                                    <div class="mt-2 text-success small d-flex align-items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                        Altura encontrada: <strong>${result.nomenclatura}</strong>.
                                    </div>
                                `;
                            } else {
                                statusDiv.innerHTML = '<div class="text-warning small mt-2">Calle encontrada, pero no se georreferenció la altura exacta.</div>';
                            }
                        } else {
                            statusDiv.innerHTML = '<div class="text-warning small mt-2">No se encontró la dirección exacta en la zona.</div>';
                        }
                    })
                    .catch(() => {
                        statusDiv.innerHTML = '';
                    });

                updateHiddenDireccion();
            }, 500);
        }

        function updateCoordinatesInput(lat, lng) {
            const val = `${lat.toFixed(6)},${lng.toFixed(6)}`;
            const el = document.getElementById('coordenadas');
            if (el) {
                el.value = val;
            }
            $wire.set('coordenadas', val);
        }

        function updateHiddenDireccion() {
            const provText = getSelectText('provincia');
            const depText = getSelectText('departamento');
            const locText = getSelectText('localidad');
            const calleVal = tsCalle.getValue() || '';
            const numeroVal = document.getElementById('numero').value.trim();
            const loteDptoVal = document.getElementById('lote_dpto').value.trim();

            let full = '';
            if (calleVal) {
                full += calleVal;
                if (numeroVal) full += ' ' + numeroVal;
            }
            if (loteDptoVal) {
                full += ' (' + loteDptoVal + ')';
            }

            const parts = [];
            if (locText && locText !== 'Selecciona una localidad') parts.push(locText);
            if (depText && depText !== 'Selecciona un departamento' && depText !== locText) parts.push(depText);
            if (provText && provText !== 'Selecciona una provincia') parts.push(provText);

            if (parts.length > 0) {
                if (full) full += ', ';
                full += parts.join(', ');
            }

            const el = document.getElementById('direccion');
            if (el) {
                el.value = full;
            }
            $wire.set('direccion', full);
        }

        function getSelectText(id) {
            const el = document.getElementById(id);
            if (!el || !el.options || el.selectedIndex < 0) return '';
            return el.options[el.selectedIndex].text;
        }

        function applyLocationFromCoords(lat, lng, targetCalle = '', targetNumero = '', targetLoteDpto = '') {
            const statusDiv = document.getElementById('geocoding-status');
            statusDiv.innerHTML = '<span class="text-muted small"><span class="spinner-border spinner-border-sm me-1" role="status"></span> Sincronizando campos desde el mapa...</span>';

            fetch(`https://apis.datos.gob.ar/georef/api/ubicacion?lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.ubicacion) {
                        const u = data.ubicacion;
                        const provId = u.provincia ? u.provincia.id : null;
                        const depId = u.departamento ? u.departamento.id : null;

                        if (provId) {
                            tsProvincia.setValue(provId);

                            fetch(`https://apis.datos.gob.ar/georef/api/departamentos?provincia=${provId}&campos=id,nombre,centroide&max=1000`)
                                .then(res => res.json())
                                .then(depData => {
                                    if (depData && depData.departamentos) {
                                        const options = depData.departamentos.map(d => {
                                            departmentCentroids[d.id] = d.centroide;
                                            return { value: d.id, text: d.nombre };
                                        });
                                        options.sort((a, b) => a.text.localeCompare(b.text));
                                        tsDepartamento.clearOptions();
                                        tsDepartamento.addOption(options);
                                        tsDepartamento.enable();

                                        if (depId) {
                                            tsDepartamento.setValue(depId);

                                            fetch(`https://apis.datos.gob.ar/georef/api/localidades?departamento=${depId}&campos=id,nombre,centroide&max=1000`)
                                                .then(res => res.json())
                                                .then(locData => {
                                                    if (locData && locData.localidades) {
                                                        const locOptions = locData.localidades.map(l => {
                                                            localityCentroids[l.id] = l.centroide;
                                                            return { value: l.id, text: l.nombre };
                                                        });
                                                        locOptions.sort((a, b) => a.text.localeCompare(b.text));
                                                        tsLocalidad.clearOptions();
                                                        tsLocalidad.addOption(locOptions);
                                                        tsLocalidad.enable();

                                                        tsCalle.enable();
                                                        document.getElementById('numero').disabled = false;

                                                        if (targetCalle) {
                                                            tsCalle.addOption({ value: targetCalle, text: targetCalle });
                                                            tsCalle.setValue(targetCalle);
                                                        }
                                                        if (targetNumero) {
                                                            document.getElementById('numero').value = targetNumero;
                                                        }
                                                        if (targetLoteDpto) {
                                                            document.getElementById('lote_dpto').value = targetLoteDpto;
                                                        }

                                                        updateHiddenDireccion();
                                                    }
                                                });
                                        }
                                    }
                                });
                        }
                        statusDiv.innerHTML = `
                            <span class="text-success small mt-2 d-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-check" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>
                                Campos sincronizados con el mapa
                            </span>
                        `;
                        setTimeout(() => { statusDiv.innerHTML = ''; }, 2000);
                    }
                })
                .catch(() => {
                    statusDiv.innerHTML = '<span class="text-danger small mt-2">Error al sincronizar campos.</span>';
                    setTimeout(() => { statusDiv.innerHTML = ''; }, 3000);
                });
        }

        initApp();
</script>
@endscript