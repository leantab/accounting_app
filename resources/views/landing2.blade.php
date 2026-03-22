<!DOCTYPE html>

<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Soberano - Gestión Empresarial Inteligente</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-tertiary-fixed-variant": "#46556b",
                        "on-primary-fixed": "#2a1700",
                        "primary-container": "#f59e0a",
                        "surface-container-high": "#141f38",
                        "on-primary-container": "#472a00",
                        "secondary-container": "#005ac2",
                        "surface-container-highest": "#192540",
                        "surface-tint": "#ffad3a",
                        "outline": "#6d758c",
                        "on-secondary-fixed": "#003271",
                        "surface-container": "#0f1930",
                        "error-container": "#b92902",
                        "error": "#ff7351",
                        "inverse-primary": "#865400",
                        "primary-fixed-dim": "#e79400",
                        "surface-bright": "#1f2b49",
                        "primary": "#ffad3a",
                        "surface-variant": "#192540",
                        "secondary-fixed": "#bfd1ff",
                        "surface-dim": "#060e20",
                        "surface-container-lowest": "#000000",
                        "tertiary-dim": "#bacae4",
                        "on-primary": "#543300",
                        "tertiary": "#d7e6ff",
                        "secondary": "#699cff",
                        "on-tertiary-container": "#3c4c61",
                        "on-surface-variant": "#a3aac4",
                        "inverse-surface": "#faf8ff",
                        "surface-container-low": "#091328",
                        "on-surface": "#dee5ff",
                        "secondary-dim": "#699cff",
                        "secondary-fixed-dim": "#a9c4ff",
                        "on-tertiary": "#45546a",
                        "primary-dim": "#e79400",
                        "tertiary-fixed": "#c8d8f3",
                        "error-dim": "#d53d18",
                        "on-secondary-container": "#f7f7ff",
                        "on-error": "#450900",
                        "on-error-container": "#ffd2c8",
                        "primary-fixed": "#f8a010",
                        "tertiary-fixed-dim": "#bacae4",
                        "background": "#060e20",
                        "on-background": "#dee5ff",
                        "on-secondary-fixed-variant": "#004da8",
                        "on-secondary": "#001e4a",
                        "outline-variant": "#40485d",
                        "inverse-on-surface": "#4d556b",
                        "on-tertiary-fixed": "#29394e",
                        "tertiary-container": "#c8d8f3",
                        "surface": "#060e20",
                        "on-primary-fixed-variant": "#563400"
                    },
                    fontFamily: {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .glass-effect {
            backdrop-filter: blur(12px);
            background: rgba(6, 14, 32, 0.7);
        }

        .amber-gradient {
            background: linear-gradient(135deg, #ffad3a 0%, #f59e0a 100%);
        }
    </style>
    <style>
        body {
            min-height: max(884px, 100dvh);
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-body selection:bg-primary/30 selection:text-primary overflow-x-hidden">
    <!-- TopAppBar -->
    <header
        class="fixed top-0 w-full z-50 bg-[#060e20] dark:bg-[#060e20] shadow-none h-16 flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-[#ffad3a]" data-icon="menu">menu</span>
            <h1 class="text-[#dee5ff] font-extrabold text-2xl tracking-tighter font-headline">Soberano</h1>
        </div>
        <div
            class="w-10 h-10 rounded-full bg-surface-container-highest flex items-center justify-center border border-outline-variant/15 overflow-hidden">
            <img alt="Admin Profile" class="w-full h-full object-cover" data-alt="Perfil de usuario administrador"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBFGGqXUPdXyHxRy8Mp0hzhJKVWd3lkJGzq5gOXikMPk_hf3PSLDh9jP6sE5Rkik2AuN2Ze8qfoAN3SG1s1DExG_CDdrvpDtRxsWN6XRfvKDl0fc8oJF1CwwaBNoBq3mkKBHTJa2uh4v0KIkqsZBzI3rg5EWv4TOMx_J1Wu82sdXCtiHYZuAzoRbRArWmfBHJSePoH3gQTEij7s8Ws_oWliCILFw-S8kh_40RmRh6E5DdHurmF8XStLbvFbQIrCVGEAQqURl0DNUbQ" />
        </div>
    </header>
    <main class="pt-16">
        <!-- Hero Section -->
        <section class="relative px-6 pt-12 pb-20 flex flex-col items-center text-center overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] h-[300px] bg-primary/10 blur-[100px] -z-10">
            </div>
            <div
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-low border border-outline-variant/15 mb-6">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <span class="text-xs font-medium text-on-surface-variant tracking-wider uppercase">Nueva Versión
                    2.0</span>
            </div>
            <h2 class="text-4xl font-extrabold font-headline tracking-tight text-on-surface mb-6 leading-[1.1]">
                Toma el control <span class="text-primary italic">Soberano</span> de tu empresa
            </h2>
            <p class="text-on-surface-variant text-base leading-relaxed mb-10 max-w-xs">
                La plataforma de gestión integral diseñada para el empresario moderno en América Latina.
            </p>
            <div class="flex flex-col w-full gap-4 px-4">
                <button
                    class="amber-gradient text-on-primary-fixed font-bold py-4 rounded-xl shadow-lg shadow-primary/10 active:scale-95 transition-transform text-lg">
                    Comenzar Gratis
                </button>
                <button
                    class="bg-surface-container-high text-on-surface font-semibold py-4 rounded-xl border border-outline-variant/10 active:scale-95 transition-transform">
                    Ver Demo
                </button>
            </div>
            <div
                class="mt-16 relative w-full aspect-[4/3] rounded-2xl bg-surface-container-low border border-outline-variant/15 overflow-hidden shadow-2xl">
                <img alt="Dashboard Preview" class="w-full h-full object-cover opacity-80"
                    data-alt="Vista previa del tablero de control financiero"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuDp0TMJBrnNdlsraOdVKKAkOeZT6tzicjMFmvwlFHJJFd1sgeNtN0r6PoQQeQ2KrBXm9fZZ7DEN12XiJNzgq5JiKHq3OSwe5Bib6aqWEcahE4t3AU9pTlJ0XN-zBea4pjjj4Bh7DjncyS4NAC3eYs2DVXTLBG4oP6ALZoLF8qCi6iSzLW2UZSk5gc18L8TP7FaXIJoR8nSxlQ5666S8NmDAJTDYwyEpY2jeGKs7hlsUUIzoFLdTN606bH0mhx6q2seMvZWXkxzXzsk" />
                <div class="absolute inset-0 bg-gradient-to-t from-surface via-transparent to-transparent"></div>
            </div>
        </section>
        <!-- Features Bento Grid - Mobile Adapated -->
        <section class="px-6 py-20 bg-surface-container-low">
            <div class="mb-12">
                <h3 class="text-primary font-bold tracking-widest text-xs uppercase mb-2">Características</h3>
                <h4 class="text-3xl font-bold font-headline text-on-surface">Potencia tu flujo de trabajo</h4>
            </div>
            <div class="space-y-6">
                <!-- Feature Card 1 -->
                <div class="p-8 rounded-3xl bg-surface-container border border-outline-variant/10">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-primary text-3xl"
                            data-icon="account_balance_wallet">account_balance_wallet</span>
                    </div>
                    <h5 class="text-xl font-bold text-on-surface mb-3">Contabilidad Ágil</h5>
                    <p class="text-on-surface-variant leading-relaxed">Automatización de facturación y reportes fiscales
                        bajo normativas locales de LATAM.</p>
                </div>
                <!-- Feature Card 2 -->
                <div class="p-8 rounded-3xl bg-surface-container border border-outline-variant/10">
                    <div class="w-12 h-12 rounded-2xl bg-secondary/10 flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-secondary text-3xl"
                            data-icon="schedule">schedule</span>
                    </div>
                    <h5 class="text-xl font-bold text-on-surface mb-3">Gestión de Tiempo</h5>
                    <p class="text-on-surface-variant leading-relaxed">Controla las horas de tu equipo y proyectos con
                        precisión milimétrica en tiempo real.</p>
                </div>
                <!-- Feature Card 3 -->
                <div
                    class="p-8 rounded-3xl bg-surface-container-highest border border-primary/20 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4">
                        <span
                            class="text-[10px] font-bold bg-primary text-on-primary-fixed px-2 py-0.5 rounded-full">BETA
                            AI</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/20 flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-primary text-3xl"
                            data-icon="psychology">psychology</span>
                    </div>
                    <h5 class="text-xl font-bold text-on-surface mb-3">IA Predictiva</h5>
                    <p class="text-on-surface-variant leading-relaxed">Anticípate a los flujos de caja y tendencias de
                        mercado con algoritmos inteligentes.</p>
                </div>
            </div>
        </section>
        <!-- Pricing Plans -->
        <section class="px-6 py-20 bg-surface">
            <div class="text-center mb-12">
                <h3 class="text-3xl font-bold font-headline text-on-surface mb-4">Planes para cada etapa</h3>
                <p class="text-on-surface-variant">Sin contratos ocultos, crece a tu propio ritmo.</p>
            </div>
            <div class="space-y-8">
                <!-- Basic Plan -->
                <div class="p-8 rounded-3xl bg-surface-container-low border border-outline-variant/10">
                    <span class="text-on-surface-variant text-sm font-semibold uppercase tracking-widest">Básico</span>
                    <div class="flex items-baseline gap-1 mt-4 mb-6">
                        <span class="text-4xl font-extrabold text-on-surface">$29</span>
                        <span class="text-on-surface-variant">/mes</span>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-on-surface-variant text-sm">
                            <span class="material-symbols-outlined text-primary text-lg"
                                data-icon="check_circle">check_circle</span>
                            Facturación Ilimitada
                        </li>
                        <li class="flex items-center gap-3 text-on-surface-variant text-sm">
                            <span class="material-symbols-outlined text-primary text-lg"
                                data-icon="check_circle">check_circle</span>
                            Hasta 3 Usuarios
                        </li>
                    </ul>
                    <button
                        class="w-full py-4 rounded-xl border border-outline-variant/30 text-on-surface font-bold">Elegir
                        Básico</button>
                </div>
                <!-- Professional Plan (Featured) -->
                <div
                    class="p-8 rounded-3xl bg-surface-container border-2 border-primary shadow-xl shadow-primary/5 relative">
                    <div
                        class="absolute -top-4 left-1/2 -translate-x-1/2 bg-primary text-on-primary-fixed px-4 py-1 rounded-full text-xs font-bold uppercase tracking-tighter">
                        Más Popular</div>
                    <span class="text-primary text-sm font-bold uppercase tracking-widest">Profesional</span>
                    <div class="flex items-baseline gap-1 mt-4 mb-6">
                        <span class="text-5xl font-extrabold text-on-surface">$79</span>
                        <span class="text-on-surface-variant">/mes</span>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-on-surface text-sm font-medium">
                            <span class="material-symbols-outlined text-primary text-lg"
                                data-icon="check_circle">check_circle</span>
                            Todo en Básico
                        </li>
                        <li class="flex items-center gap-3 text-on-surface text-sm font-medium">
                            <span class="material-symbols-outlined text-primary text-lg"
                                data-icon="check_circle">check_circle</span>
                            Reportes de IA
                        </li>
                        <li class="flex items-center gap-3 text-on-surface text-sm font-medium">
                            <span class="material-symbols-outlined text-primary text-lg"
                                data-icon="check_circle">check_circle</span>
                            Integración Bancaria
                        </li>
                    </ul>
                    <button
                        class="w-full py-4 rounded-xl amber-gradient text-on-primary-fixed font-bold shadow-lg shadow-primary/20">Elegir
                        Profesional</button>
                </div>
                <!-- Enterprise Plan -->
                <div class="p-8 rounded-3xl bg-surface-container-low border border-outline-variant/10">
                    <span
                        class="text-on-surface-variant text-sm font-semibold uppercase tracking-widest">Empresarial</span>
                    <div class="flex items-baseline gap-1 mt-4 mb-6">
                        <span class="text-4xl font-extrabold text-on-surface">Custom</span>
                    </div>
                    <p class="text-on-surface-variant text-sm mb-8 leading-relaxed">Soluciones personalizadas para
                        corporativos con necesidades de alta escala.</p>
                    <button
                        class="w-full py-4 rounded-xl border border-outline-variant/30 text-on-surface font-bold">Contactar
                        Ventas</button>
                </div>
            </div>
        </section>
        <!-- Final CTA -->
        <section class="px-6 py-24 text-center bg-surface-container-highest relative overflow-hidden">
            <div
                class="absolute bottom-0 left-0 w-full h-full bg-gradient-to-t from-primary/5 to-transparent pointer-events-none">
            </div>
            <h2 class="text-3xl font-extrabold font-headline mb-6 text-on-surface">¿Listo para modernizar tu negocio?
            </h2>
            <p class="text-on-surface-variant mb-10 max-w-xs mx-auto">Únete a más de 5,000 empresarios que ya confían
                en la soberanía de sus datos.</p>
            <button
                class="w-full max-w-sm amber-gradient text-on-primary-fixed font-bold py-5 rounded-2xl text-xl shadow-xl shadow-primary/20 active:scale-95 transition-transform">
                Empezar Ahora
            </button>
            <p class="mt-6 text-xs text-on-surface-variant font-medium">No requiere tarjeta de crédito • 14 días gratis
            </p>
        </section>
    </main>
    <!-- Footer -->
    <footer
        class="w-full border-t border-[#40485d]/15 bg-[#060e20] flex flex-col items-center gap-6 py-12 px-8 text-center">
        <div class="text-[#dee5ff] font-bold text-lg font-headline">Soberano</div>
        <nav class="flex flex-wrap justify-center gap-x-6 gap-y-4">
            <a class="text-[#a3aac4] font-inter text-sm font-medium hover:text-[#f59e0a] transition-all cursor-pointer"
                href="#">Facturación</a>
            <a class="text-[#a3aac4] font-inter text-sm font-medium hover:text-[#f59e0a] transition-all cursor-pointer"
                href="#">Cuentas por Cobrar</a>
            <a class="text-[#a3aac4] font-inter text-sm font-medium hover:text-[#f59e0a] transition-all cursor-pointer"
                href="#">Reportes</a>
            <a class="text-[#a3aac4] font-inter text-sm font-medium hover:text-[#f59e0a] transition-all cursor-pointer"
                href="#">Soporte</a>
        </nav>
        <p class="text-[#a3aac4] font-inter text-xs opacity-60">© 2024 Soberano SaaS. Todos los derechos reservados.
        </p>
    </footer>
    <!-- Bottom Nav for Mobile Context -->
    <nav
        class="fixed bottom-0 left-0 w-full glass-effect border-t border-outline-variant/10 flex justify-around items-center h-16 md:hidden z-50">
        <a class="flex flex-col items-center gap-1 text-[#ffad3a] font-semibold" href="#">
            <span class="material-symbols-outlined" data-icon="home"
                style="font-variation-settings: 'FILL' 1;">home</span>
            <span class="text-[10px]">Inicio</span>
        </a>
        <a class="flex flex-col items-center gap-1 text-[#a3aac4] hover:text-[#dee5ff] transition-colors duration-300"
            href="#">
            <span class="material-symbols-outlined" data-icon="explore">explore</span>
            <span class="text-[10px]">Explorar</span>
        </a>
        <a class="flex flex-col items-center gap-1 text-[#a3aac4] hover:text-[#dee5ff] transition-colors duration-300"
            href="#">
            <span class="material-symbols-outlined" data-icon="insights">insights</span>
            <span class="text-[10px]">Planes</span>
        </a>
        <a class="flex flex-col items-center gap-1 text-[#a3aac4] hover:text-[#dee5ff] transition-colors duration-300"
            href="#">
            <span class="material-symbols-outlined" data-icon="person">person</span>
            <span class="text-[10px]">Acceso</span>
        </a>
    </nav>
    <!-- Spacer for bottom nav -->
    <div class="h-16 md:hidden"></div>
</body>

</html>
