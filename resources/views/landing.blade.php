<!DOCTYPE html>

<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Soberano | Gestión Inteligente para tu Empresa</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap"
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
                        "on-tertiary-fixed": "#29394e",
                        "tertiary-fixed-dim": "#bacae4",
                        "inverse-surface": "#faf8ff",
                        "inverse-on-surface": "#4d556b",
                        "on-primary-container": "#472a00",
                        "outline": "#6d758c",
                        "on-error": "#450900",
                        "surface-container-low": "#091328",
                        "tertiary-dim": "#bacae4",
                        "on-primary": "#543300",
                        "secondary-container": "#005ac2",
                        "on-secondary-fixed": "#003271",
                        "outline-variant": "#40485d",
                        "background": "#060e20",
                        "primary-container": "#f59e0a",
                        "on-secondary-container": "#f7f7ff",
                        "surface-container": "#0f1930",
                        "error": "#ff7351",
                        "surface-variant": "#192540",
                        "surface-container-highest": "#192540",
                        "surface-tint": "#ffad3a",
                        "on-secondary": "#001e4a",
                        "secondary": "#699cff",
                        "inverse-primary": "#865400",
                        "error-container": "#b92902",
                        "primary-fixed": "#f8a010",
                        "surface-container-lowest": "#000000",
                        "on-primary-fixed": "#2a1700",
                        "on-tertiary-container": "#3c4c61",
                        "tertiary-fixed": "#c8d8f3",
                        "on-secondary-fixed-variant": "#004da8",
                        "primary": "#ffad3a",
                        "on-primary-fixed-variant": "#563400",
                        "on-tertiary": "#45546a",
                        "on-surface": "#dee5ff",
                        "on-error-container": "#ffd2c8",
                        "surface-container-high": "#141f38",
                        "surface-bright": "#1f2b49",
                        "secondary-dim": "#699cff",
                        "primary-dim": "#e79400",
                        "surface-dim": "#060e20",
                        "surface": "#060e20",
                        "error-dim": "#d53d18",
                        "on-surface-variant": "#a3aac4",
                        "tertiary": "#d7e6ff",
                        "on-background": "#dee5ff",
                        "secondary-fixed-dim": "#a9c4ff",
                        "secondary-fixed": "#bfd1ff",
                        "tertiary-container": "#c8d8f3",
                        "primary-fixed-dim": "#e79400"
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
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3 {
            font-family: 'Manrope', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .bg-amber-gradient {
            background: linear-gradient(135deg, #ffad3a 0%, #f59e0a 100%);
        }

        .bg-glass {
            backdrop-filter: blur(12px);
            background: rgba(6, 14, 32, 0.7);
        }
    </style>
</head>

<body class="bg-surface text-on-surface selection:bg-primary/30">
    <!-- TopNavBar -->
    <nav class="fixed top-0 w-full z-50 bg-[#060e20]/80 backdrop-blur-xl shadow-2xl shadow-black/20">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img class="h-8 w-8 object-contain" data-alt="Logotipo minimalista Soberano color ámbar"
                    src="{{ asset('icon3.png') }}" />
                <span class="text-xl font-black text-[#dee5ff] tracking-tight">{{ config('app.name') }}</span>
            </div>
            <div class="hidden md:flex items-center space-gap-8 gap-8">
                <a class="font-['Manrope'] font-bold text-sm tracking-wide text-[#ffad3a] border-b-2 border-[#f59e0a] pb-1"
                    href="#">Inicio</a>
                <a class="font-['Manrope'] font-bold text-sm tracking-wide text-[#a3aac4] hover:text-[#dee5ff] transition-colors"
                    href="#features">Funciones</a>
                <a class="font-['Manrope'] font-bold text-sm tracking-wide text-[#a3aac4] hover:text-[#dee5ff] transition-colors"
                    href="#pricing">Precios</a>
                <a class="font-['Manrope'] font-bold text-sm tracking-wide text-[#a3aac4] hover:text-[#dee5ff] transition-colors"
                    href="#contact">Contacto</a>
            </div>
            <div class="flex items-center gap-4">
                <button
                    class="bg-amber-gradient text-on-primary-fixed px-5 py-2 rounded-lg font-bold text-sm transition-transform active:scale-95 duration-150"
                    onclick="window.location.href = '/app/login'">
                    Ingresar
                </button>
                <button class="md:hidden text-on-surface">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>
    </nav>
    <main class="pt-24">
        <!-- Hero Section -->
        <section class="relative overflow-hidden py-20 px-6">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-secondary/10 blur-[100px] rounded-full">
            </div>
            <div class="max-w-7xl mx-auto relative z-10 grid md:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-high border border-outline-variant/20 text-xs font-semibold text-primary tracking-widest uppercase">
                        <span class="material-symbols-outlined text-sm"
                            style="font-variation-settings: 'FILL' 1;">bolt</span>
                        Nueva Era de Gestión
                    </div>
                    <h1 class="text-5xl md:text-7xl font-extrabold text-on-surface leading-[1.1] tracking-tight">
                        Soberano: Gestión <span class="text-primary italic">Inteligente</span> para tu Empresa
                    </h1>
                    <p class="text-lg text-on-surface-variant max-w-lg leading-relaxed font-body">
                        Contabilidad, Control de Tiempo y Gestión con IA en un solo lugar. Optimiza tus finanzas y
                        ahorra tiempo cada día con tecnología editorial de alta gama.
                    </p>
                    <div class="flex flex-wrap gap-4 pt-4">
                        <button
                            class="bg-amber-gradient text-on-primary-fixed px-8 py-4 rounded-xl font-bold text-base shadow-xl shadow-primary/20 hover:shadow-primary/40 transition-all active:scale-95">
                            Prueba Gratis
                        </button>
                        <button
                            class="bg-surface-container-high text-on-surface px-8 py-4 rounded-xl font-bold text-base border border-outline-variant/30 hover:bg-surface-variant transition-all flex items-center gap-2">
                            Ver Demo
                            <span class="material-symbols-outlined">play_circle</span>
                        </button>
                    </div>
                </div>
                <div class="relative group">
                    <div
                        class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-secondary/20 rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-1000">
                    </div>
                    <div
                        class="relative bg-surface-container-low rounded-2xl border border-outline-variant/10 shadow-2xl overflow-hidden">
                        <img class="w-full h-auto opacity-90"
                            data-alt="Dashboard administrativo moderno con analíticas financieras de Soberano"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBx4Kq48zNoS-rGtPJtszHp_M1Ef9vh2ne1xVT5CtK62FN1VwtuwhTvgvxC9Sga5Af76IBMrvlNgibX3Yyskf-Kzx_o1mzzLsu6YcrUuf2uu2ELNP6lHFYAizCVrVL_sKyKYijqpn0zQEHcQvhkSVCu9pPzFyovETsugv1wP4o8tNpDrNASF3EytmZIdrWBH1wGm6d0BS93_eO033owk4tfl7PiU52ZkkV4sWK2ohchYgk9WbEAnlJuk1AbAa-O0OBc2-gVj2loeEM" />
                    </div>
                </div>
            </div>
        </section>
        <!-- Bento Grid Features -->
        <section class="py-24 px-6 bg-surface-container-low" id="features">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16 space-y-4">
                    <h2 class="text-3xl md:text-5xl font-bold text-on-surface tracking-tight">Potencia tu flujo de
                        trabajo</h2>
                    <p class="text-on-surface-variant max-w-2xl mx-auto">Diseñado para la precisión empresarial. Sin
                        líneas distractoras, solo datos puros y control total.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Feature 1 -->
                    <div
                        class="bg-surface-container p-8 rounded-2xl border border-outline-variant/5 hover:border-primary/20 transition-all group">
                        <div
                            class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-6 text-primary group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
                        </div>
                        <h3 class="text-xl font-bold text-on-surface mb-3">Contabilidad simplificada</h3>
                        <p class="text-on-surface-variant leading-relaxed font-body">
                            Automatiza tus facturas y mantén tus cuentas claras sin complicaciones. Reportes fiscales
                            generados en segundos.
                        </p>
                    </div>
                    <!-- Feature 2 -->
                    <div
                        class="bg-surface-container p-8 rounded-2xl border border-outline-variant/5 hover:border-secondary/20 transition-all group">
                        <div
                            class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center mb-6 text-secondary group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-3xl">schedule</span>
                        </div>
                        <h3 class="text-xl font-bold text-on-surface mb-3">Seguimiento de tiempo</h3>
                        <p class="text-on-surface-variant leading-relaxed font-body">
                            Controla las horas de tu equipo en tiempo real y gestiona nóminas fácilmente con integración
                            directa.
                        </p>
                    </div>
                    <!-- Feature 3 -->
                    <div
                        class="bg-surface-container p-8 rounded-2xl border border-outline-variant/5 hover:border-primary/20 transition-all group">
                        <div
                            class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-6 text-primary group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-3xl">psychology</span>
                        </div>
                        <h3 class="text-xl font-bold text-on-surface mb-3">Inteligencia Artificial</h3>
                        <p class="text-on-surface-variant leading-relaxed font-body">
                            Nuestra IA analiza tus pagos y facturas para darte consejos inteligentes y predicciones de
                            flujo de caja.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <!-- Pricing Section -->
        <section class="py-24 px-6 bg-surface" id="pricing">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-extrabold text-on-surface mb-4">Planes para cada etapa</h2>
                    <p class="text-on-surface-variant">Escala tu negocio con la infraestructura soberana definitiva.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Plan Basic -->
                    <div class="bg-surface-container p-10 rounded-2xl border border-outline-variant/10 flex flex-col">
                        <h4 class="text-lg font-bold text-on-surface-variant mb-2">Profesional</h4>
                        <div class="flex items-baseline gap-1 mb-6">
                            <span class="text-4xl font-black text-on-surface">U$D 29</span>
                            <span class="text-on-surface-variant">/mes</span>
                        </div>
                        <ul class="space-y-4 mb-10 flex-grow">
                            <li class="flex items-center gap-3 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-secondary text-sm">check_circle</span>
                                Hasta 5 usuarios
                            </li>
                            <li class="flex items-center gap-3 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-secondary text-sm">check_circle</span>
                                Contabilidad básica
                            </li>
                            <li class="flex items-center gap-3 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-secondary text-sm">check_circle</span>
                                Reportes mensuales
                            </li>
                        </ul>
                        <button
                            class="w-full py-3 rounded-lg border border-outline-variant/40 font-bold text-on-surface hover:bg-surface-variant transition-colors">
                            Seleccionar Plan
                        </button>
                    </div>
                    <!-- Plan Professional (Featured) -->
                    <div
                        class="bg-surface-container-high p-10 rounded-2xl border-2 border-primary ring-4 ring-primary/5 flex flex-col relative scale-105 shadow-2xl z-10">
                        <div
                            class="absolute top-0 right-10 -translate-y-1/2 bg-amber-gradient text-on-primary-fixed px-4 py-1 rounded-full text-xs font-black uppercase tracking-wider">
                            Popular
                        </div>
                        <h4 class="text-lg font-bold text-primary mb-2">PyME</h4>
                        <div class="flex items-baseline gap-1 mb-6">
                            <span class="text-4xl font-black text-on-surface">U$D 79</span>
                            <span class="text-on-surface-variant">/mes</span>
                        </div>
                        <ul class="space-y-4 mb-10 flex-grow">
                            <li class="flex items-center gap-3 text-sm text-on-surface">
                                <span class="material-symbols-outlined text-primary text-sm"
                                    style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                Usuarios ilimitados
                            </li>
                            <li class="flex items-center gap-3 text-sm text-on-surface">
                                <span class="material-symbols-outlined text-primary text-sm"
                                    style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                IA de análisis financiero
                            </li>
                            <li class="flex items-center gap-3 text-sm text-on-surface">
                                <span class="material-symbols-outlined text-primary text-sm"
                                    style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                Gestión de nóminas Pro
                            </li>
                            <li class="flex items-center gap-3 text-sm text-on-surface">
                                <span class="material-symbols-outlined text-primary text-sm"
                                    style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                Soporte prioritario 24/7
                            </li>
                        </ul>
                        <button
                            class="w-full py-3 rounded-lg bg-amber-gradient text-on-primary-fixed font-bold shadow-lg shadow-primary/20 hover:scale-[1.02] transition-transform">
                            Comenzar ahora
                        </button>
                    </div>
                    <!-- Plan Enterprise -->
                    <div class="bg-surface-container p-10 rounded-2xl border border-outline-variant/10 flex flex-col">
                        <h4 class="text-lg font-bold text-on-surface-variant mb-2">Empresa</h4>
                        <div class="flex items-baseline gap-1 mb-6">
                            <span class="text-4xl font-black text-on-surface">U$D 199</span>
                            <span class="text-on-surface-variant">/mes</span>
                        </div>
                        <ul class="space-y-4 mb-10 flex-grow">
                            <li class="flex items-center gap-3 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-secondary text-sm">check_circle</span>
                                Todo en Profesional
                            </li>
                            <li class="flex items-center gap-3 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-secondary text-sm">check_circle</span>
                                API de acceso total
                            </li>
                            <li class="flex items-center gap-3 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined text-secondary text-sm">check_circle</span>
                                Servidores dedicados
                            </li>
                        </ul>
                        <button
                            class="w-full py-3 rounded-lg border border-outline-variant/40 font-bold text-on-surface hover:bg-surface-variant transition-colors">
                            Contactar Ventas
                        </button>
                    </div>
                </div>
            </div>
        </section>
        <!-- CTA Final -->
        <section class="py-20 px-6">
            <div
                class="max-w-4xl mx-auto bg-amber-gradient rounded-3xl p-12 text-center text-on-primary-container relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 blur-3xl rounded-full -mr-32 -mt-32"></div>
                <h2 class="text-3xl md:text-5xl font-extrabold mb-6 relative z-10">¿Listo para tomar el control
                    soberano?</h2>
                <p class="text-lg font-medium mb-10 opacity-90 relative z-10">Únete a más de 5,000 empresas que
                    optimizan sus finanzas con nosotros.</p>
                <div class="flex flex-wrap justify-center gap-4 relative z-10">
                    <button
                        class="bg-[#2a1700] text-white px-10 py-4 rounded-xl font-bold text-lg hover:bg-black transition-colors">
                        Empieza Gratis hoy
                    </button>
                </div>
            </div>
        </section>
    </main>
    <!-- Footer -->
    <footer class="bg-[#091328] py-12 px-6 border-t border-outline-variant/10">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">
            <div class="space-y-4">
                <div class="text-lg font-bold text-[#dee5ff]">Soberano</div>
                <p class="text-sm text-[#a3aac4] font-body leading-relaxed">
                    Elevando la gestión empresarial en América Latina a través de tecnología precisa y diseño editorial.
                </p>
            </div>
            <div>
                <h5 class="text-on-surface font-bold mb-4">Producto</h5>
                <ul class="space-y-2 text-sm text-[#a3aac4]">
                    <li><a class="hover:text-[#dee5ff] hover:underline decoration-[#f59e0a]"
                            href="#">Funciones</a></li>
                    <li><a class="hover:text-[#dee5ff] hover:underline decoration-[#f59e0a]"
                            href="#">Integraciones</a></li>
                    <li><a class="hover:text-[#dee5ff] hover:underline decoration-[#f59e0a]" href="#">API</a>
                    </li>
                </ul>
            </div>
            <div>
                <h5 class="text-on-surface font-bold mb-4">Compañía</h5>
                <ul class="space-y-2 text-sm text-[#a3aac4]">
                    <li><a class="hover:text-[#dee5ff] hover:underline decoration-[#f59e0a]"
                            href="#">Privacidad</a></li>
                    <li><a class="hover:text-[#dee5ff] hover:underline decoration-[#f59e0a]"
                            href="#">Términos</a></li>
                    <li><a class="hover:text-[#dee5ff] hover:underline decoration-[#f59e0a]"
                            href="#">Soporte</a></li>
                </ul>
            </div>
            <div>
                <h5 class="text-on-surface font-bold mb-4">Newsletter</h5>
                <div class="flex gap-2">
                    <input class="bg-surface-container border-none rounded-lg text-sm w-full focus:ring-primary"
                        placeholder="Tu email" type="email" />
                    <button class="bg-primary text-on-primary-fixed p-2 rounded-lg">
                        <span class="material-symbols-outlined">send</span>
                    </button>
                </div>
            </div>
        </div>
        <div
            class="max-w-7xl mx-auto mt-12 pt-8 border-t border-outline-variant/10 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm text-[#a3aac4]">© 2024 Soberano SaaS. Todos los derechos reservados.</p>
            <div class="flex gap-6">
                <span
                    class="material-symbols-outlined text-[#a3aac4] hover:text-primary cursor-pointer transition-colors">public</span>
                <span
                    class="material-symbols-outlined text-[#a3aac4] hover:text-primary cursor-pointer transition-colors">hub</span>
                <span
                    class="material-symbols-outlined text-[#a3aac4] hover:text-primary cursor-pointer transition-colors">token</span>
            </div>
        </div>
    </footer>
</body>

</html>
