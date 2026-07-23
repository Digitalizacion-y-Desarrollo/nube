<x-layouts.auth title="Iniciar sesión">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="login-brand-panel relative hidden min-h-screen overflow-hidden p-16 text-white lg:flex lg:flex-col lg:justify-between">
            <img
                src="{{ asset('assets/figma/login-circle-1.svg') }}"
                alt=""
                width="360"
                height="360"
                class="pointer-events-none absolute -right-[120px] top-[120px] size-[360px]"
            >
            <img
                src="{{ asset('assets/figma/login-circle-2.svg') }}"
                alt=""
                width="420"
                height="420"
                class="pointer-events-none absolute -bottom-[120px] -left-[140px] size-[420px]"
            >

            <img
                src="{{ asset('assets/figma/logo-nezahualcoyotl.png') }}"
                alt="Escudo del H. Ayuntamiento de Nezahualcóyotl"
                width="120"
                height="90"
                class="relative z-10 h-[90px] w-[120px] object-cover"
            >

            <div class="relative z-10 inline-flex w-fit items-center gap-2.5 rounded-[10px] border border-gold bg-white/[0.08] px-3.5 py-2.5">
                <x-ui.icon name="shield" :size="18" alt="" />
                <span class="text-xs font-extrabold uppercase">Gobierno Municipal de Nezahualcóyotl</span>
            </div>

            <div class="relative z-10 max-w-[592px]">
                <p class="mb-2.5 text-sm font-bold uppercase text-gold">H. Ayuntamiento</p>
                <h1 class="text-[44px] font-bold leading-[1.1]">Nube Municipal</h1>
                <p class="mt-2.5 text-base leading-6 text-white/70">Sistema interno de gestión y almacenamiento de archivos</p>

                <article class="mt-5 rounded-[20px] border border-white/15 bg-white/[0.05] p-6 backdrop-blur-xl">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-white/[0.08]">
                            <x-ui.icon name="folder-lock" :size="20" alt="" />
                        </span>
                        <span class="rounded-full border border-gold bg-gold/15 px-2.5 py-1.5 text-[11px] font-bold">SISTEMA SEGURO</span>
                    </div>
                    <h2 class="text-base font-bold">Infraestructura Nube Gob</h2>
                    <p class="mt-2 text-[13px] leading-5 text-white/70">Resguardo digital de expedientes oficiales con cifrado avanzado de grado gubernamental.</p>
                    <img src="{{ asset('assets/figma/login-line.svg') }}" alt="" width="544" height="1" class="mt-3 h-px w-full">
                </article>
            </div>

            <div class="relative z-10 flex items-center gap-3 text-[11px] font-semibold text-white/50">
                <span>Dirección de Tecnologías de la Información</span>
                <span class="size-1 rounded-sm bg-gold"></span>
                <span class="text-gold">México</span>
            </div>
        </section>

        <section class="relative flex min-h-screen flex-col items-center justify-between bg-surface px-5 py-8 sm:px-12 lg:px-20 lg:py-12">
            <div class="absolute right-5 top-5 sm:right-8 sm:top-8">
                <x-ui.theme-toggle />
            </div>

            <div class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-md bg-brand text-sm font-extrabold text-gold">NE</span>
                <span class="text-xs font-bold text-muted">NUBE MUNICIPAL</span>
            </div>

            <form action="{{ route('login.store') }}" method="POST" class="my-10 w-full max-w-[560px] lg:my-0" novalidate>
                @csrf

                <div class="mb-6">
                    <h2 class="text-2xl font-bold leading-tight text-brand dark:text-white">Iniciar Sesión</h2>
                    <p class="mt-1.5 text-sm leading-5 text-muted">Accede a tu espacio seguro de archivos</p>
                </div>

                @if (session('auth_error'))
                    <x-ui.alert class="mb-4">{{ session('auth_error') }}</x-ui.alert>
                @endif

                <div class="space-y-4">
                    <x-ui.input
                        label="Correo electrónico"
                        name="email"
                        type="email"
                        autocomplete="email"
                        inputmode="email"
                        placeholder="nombre@nezahualcoyotl.gob.mx"
                        :value="old('email')"
                        :error="$errors->first('email')"
                        required
                        autofocus
                    />

                    <x-ui.input
                        label="Contraseña"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        :error="$errors->first('password')"
                        data-password-input
                        class="pr-12"
                        required
                    >
                        <x-slot:suffix>
                            <button type="button" data-password-toggle class="absolute inset-y-0 right-3.5 flex items-center" aria-label="Mostrar contraseña" aria-pressed="false">
                                <x-ui.icon name="eye" :size="18" alt="" />
                            </button>
                        </x-slot:suffix>
                    </x-ui.input>
                </div>

                <label class="mt-6 flex w-fit cursor-pointer items-center gap-2 text-[13px] text-muted">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        @checked(old('remember'))
                        class="size-[18px] rounded border-[1.5px] border-brand text-brand accent-brand focus:ring-brand"
                    >
                    <span>Mantener mi sesión iniciada</span>
                </label>

                <x-ui.button type="submit" class="mt-6 h-12 w-full">Iniciar Sesión</x-ui.button>

                <a href="https://accesos.digitalneza.com" class="mt-4 block text-center text-xs font-semibold text-brand hover:underline dark:text-white">
                    ¿Olvidaste tu contraseña?
                </a>
            </form>

            <footer class="flex w-full max-w-[560px] flex-col items-center gap-4 text-center text-muted">
                <div class="flex items-center gap-1.5 text-xs">
                    <x-ui.icon name="lock" :size="14" alt="" />
                    <span>Conexión segura • SSL/TLS</span>
                </div>
                <div class="space-y-1">
                    <p class="text-[11px]">Nube Municipal v1.0.0</p>
                    <p class="text-[10px]">Uso exclusivo para personal autorizado del H. Ayuntamiento de Nezahualcóyotl</p>
                </div>
            </footer>
        </section>
    </main>
</x-layouts.auth>
