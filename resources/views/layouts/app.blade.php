<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Poliplacas')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    <div class="page">
        <!-- Sidebar -->
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark m-0 px-3 py-4">
                    <a href="/" class="d-flex align-items-center justify-content-center w-100">
                        <div class="bg-white p-3 rounded-3 shadow-sm d-flex align-items-center justify-content-center w-100" style="max-height: 85px;">
                            <img src="{{ asset('images/logo.png') }}" alt="Poliplacas S.A." style="max-height: 65px; width: 100%; object-fit: contain;">
                        </div>
                    </a>
                </h1>
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <li class="nav-item {{ Request::is('/') ? 'active' : '' }}">
                            <a class="nav-link" href="/">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                                </span>
                                <span class="nav-link-title">Inicio</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('entidades*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('entidades.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                                </span>
                                <span class="nav-link-title">Entidades</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('caja*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('cajas.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-wallet" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                       <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"></path>
                                       <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4z"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Caja</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('cheques*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('cheques.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                       <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"></path>
                                       <path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"></path>
                                       <path d="M12 7v10"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Cheques</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
        
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl py-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 10000; max-width: 380px; width: 100%;"></div>

    <style>
        .custom-toast {
            backdrop-filter: blur(8px);
            border-radius: 10px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important;
        }
        .custom-toast.bg-success {
            background-color: rgba(46, 204, 113, 0.9) !important;
            color: #fff !important;
        }
        .custom-toast.bg-danger {
            background-color: rgba(231, 76, 60, 0.9) !important;
            color: #fff !important;
        }
        .custom-toast.bg-warning {
            background-color: rgba(241, 196, 15, 0.95) !important;
            color: #1a252f !important;
        }
    </style>

    <script>
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            let bgClass = 'bg-success';
            let icon = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="9" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>`;
            
            if (type === 'error') {
                bgClass = 'bg-danger';
                icon = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="9" />
                            <line x1="10" y1="10" x2="14" y2="14" />
                            <line x1="14" y1="10" x2="10" y2="14" />
                        </svg>`;
            } else if (type === 'warning') {
                bgClass = 'bg-warning';
                icon = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="9" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>`;
            }

            const id = 'toast-' + Math.random().toString(36).substr(2, 9);
            const html = `
                <div id="${id}" class="toast custom-toast align-items-center ${bgClass} border-0 shadow-lg mb-2 show" role="alert" aria-live="assertive" aria-atomic="true" style="opacity: 0; transform: translateY(-20px) scale(0.9);">
                    <div class="d-flex p-3 align-items-center">
                        <div class="me-3">${icon}</div>
                        <div class="toast-body fw-bold flex-grow-1 p-0" style="font-size: 0.95rem; line-height: 1.4;">${message}</div>
                        <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'} ms-2" data-bs-dismiss="toast" aria-label="Close" onclick="this.closest('.toast').remove()"></button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            const element = document.getElementById(id);
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0) scale(1)';
            }, 50);

            setTimeout(() => {
                if (element) {
                    element.style.opacity = '0';
                    element.style.transform = 'translateY(-20px) scale(0.9)';
                    setTimeout(() => element.remove(), 350);
                }
            }, 4000);
        };

        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                window.showToast(data.message, data.type);
            });

            Livewire.hook('commit', ({ component, succeed, fail }) => {
                succeed(({ snapshot, effect }) => {
                    if (effect && effect.errors && Object.keys(effect.errors).length > 0) {
                        window.showToast("Faltan campos requeridos o hay datos inválidos.", "warning");
                    }
                });
                fail(({ status, content }) => {
                    window.showToast("Error en el servidor (" + status + "). Intente nuevamente.", "error");
                });
            });
        });

        // Trigger flash toasts if they exist in session
        @if(session('success'))
            window.addEventListener('DOMContentLoaded', () => {
                window.showToast("{{ session('success') }}", "success");
            });
        @endif
        @if(session('error'))
            window.addEventListener('DOMContentLoaded', () => {
                window.showToast("{{ session('error') }}", "error");
            });
        @endif
        @if(session('warning'))
            window.addEventListener('DOMContentLoaded', () => {
                window.showToast("{{ session('warning') }}", "warning");
            });
        @endif
    </script>

    @livewireScripts
    @stack('scripts')
</body>
</html>