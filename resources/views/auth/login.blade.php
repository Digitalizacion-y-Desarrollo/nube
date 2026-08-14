<x-layouts.auth title="Iniciar sesión">
    <main class="min-h-screen bg-white lg:grid lg:grid-cols-2">
        <section class="login-brand-panel relative flex h-[324px] overflow-hidden px-6 py-3 text-white lg:min-h-screen lg:h-auto lg:flex-col lg:justify-between lg:p-16">
            <img src="{{ asset('assets/figma/login-circle-1.svg') }}" alt="" class="pointer-events-none absolute -right-[30px] -top-10 size-[180px] lg:-right-[120px] lg:top-[120px] lg:size-[360px]">
            <img src="{{ asset('assets/figma/login-circle-2.svg') }}" alt="" class="pointer-events-none absolute -bottom-9 -left-[60px] size-[200px] lg:-bottom-[120px] lg:-left-[140px] lg:size-[420px]">

            <x-ui.brand-logo
                width="135"
                height="90"
                class="relative z-10 h-[60px] w-[90px] rounded-lg shadow-lg lg:h-[90px] lg:w-[135px]"
            />

            <div class="absolute left-6 right-6 top-[97px] z-10 flex items-center justify-between lg:static lg:block">
                <div class="inline-flex items-center gap-2 rounded-[10px] border border-gold bg-white/[0.08] px-3 py-2 lg:px-3.5 lg:py-2.5">
                    <x-ui.icon name="shield" :size="16" alt="" />
                    <span class="text-[10px] font-extrabold uppercase lg:text-xs">Gobierno Municipal<span class="hidden lg:inline"> de Nezahualcóyotl</span></span>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-gold bg-gold/15 px-2 py-1 text-[9px] font-bold lg:hidden">
                    <x-ui.icon name="folder-lock" :size="12" alt="" />
                    SISTEMA SEGURO
                </span>
            </div>

            <div class="absolute left-6 right-6 top-[157px] z-10 lg:static lg:max-w-[592px]">
                <div class="mb-2 flex items-center gap-2 lg:block">
                    <x-ui.brand-logo alt="" width="36" height="24" class="h-6 w-9 rounded lg:hidden" />
                    <p class="text-[11px] font-bold uppercase text-gold lg:mb-2.5 lg:text-sm">H. Ayuntamiento</p>
                </div>
                <h1 class="text-[28px] font-bold leading-[1.1] lg:text-[44px]">Nube Empresarial</h1>
                <p class="mt-2 text-[13px] leading-[1.4] text-white/70 lg:mt-2.5 lg:text-base lg:leading-6">Sistema interno de gestión y almacenamiento de archivos<span class="lg:hidden"> oficiales.</span></p>

                <article class="mt-5 hidden rounded-[20px] border border-white/15 bg-white/[0.05] p-6 backdrop-blur-xl lg:block">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-white/[0.08]">
                            <x-ui.icon name="folder-lock" :size="20" alt="" />
                        </span>
                        <span class="rounded-full border border-gold bg-gold/15 px-2.5 py-1.5 text-[11px] font-bold">SISTEMA SEGURO</span>
                    </div>
                    <h2 class="text-base font-bold">Infraestructura Nube Gob</h2>
                    <p class="mt-2 text-[13px] leading-5 text-white/70">Resguardo digital de expedientes oficiales con cifrado avanzado de grado gubernamental.</p>
                    <img src="{{ asset('assets/figma/login-line.svg') }}" alt="" class="mt-3 h-px w-full">
                </article>
            </div>

            <div class="relative z-10 hidden items-center gap-3 text-[11px] font-semibold text-white/50 lg:flex">
                <span>Dirección de Tecnologías de la Información</span>
                <span class="size-1 rounded-sm bg-gold"></span>
                <span class="text-gold">México</span>
            </div>
        </section>

        <section class="relative flex min-h-[520px] flex-col items-center bg-white px-6 py-6 lg:min-h-screen lg:justify-between lg:px-20 lg:py-12">
            <div class="hidden items-center gap-2 lg:flex">
                <x-ui.brand-logo alt="" width="48" height="32" class="h-8 w-12 rounded-md" />
                <span class="text-xs font-bold text-[#4b5563]">NUBE EMPRESARIAL</span>
            </div>

            <form action="{{ route('login.store') }}" method="POST" class="w-full max-w-[560px] lg:my-auto" novalidate data-login-form>
                @csrf

                <div class="mb-5 lg:mb-6">
                    <h2 class="text-2xl font-bold leading-tight text-brand">Iniciar Sesión</h2>
                    <p class="mt-1 text-sm leading-5 text-[#4b5563]">Accede a tu espacio seguro de archivos</p>
                </div>

                @if (session('status'))
                    <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
                @endif

                @if (session('auth_error'))
                    <x-ui.alert :tone="session('auth_error_type') === 'permission' || session('auth_error_type') === 'inactive' ? 'info' : 'error'" class="mb-4">
                        {{ session('auth_error') }}
                    </x-ui.alert>
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
                            <button type="button" data-password-toggle class="absolute inset-y-0 right-3.5 flex items-center text-[#6b7280]" aria-label="Mostrar contraseña" aria-pressed="false">
                                <x-ui.icon name="eye" :size="18" alt="" />
                            </button>
                        </x-slot:suffix>
                    </x-ui.input>
                </div>

                <label class="mt-5 flex w-fit cursor-pointer items-center gap-2 text-[13px] text-[#4b5563] lg:mt-6">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-[18px] rounded border-[1.5px] border-brand accent-brand focus:ring-brand">
                    <span>Mantener mi sesión iniciada</span>
                </label>

                @if (session('auth_error_type') === 'connection')
                    <div class="mt-5 grid grid-cols-2 gap-3 lg:mt-6">
                        <button type="button" onclick="window.location.reload()" class="h-12 rounded-lg border border-brand text-sm font-semibold text-brand">Reintentar</button>
                        <x-ui.button type="submit" class="h-12 w-full" data-login-submit>
                            <span data-login-label>Iniciar Sesión</span>
                        </x-ui.button>
                    </div>
                @else
                    <x-ui.button type="submit" class="mt-5 h-12 w-full lg:mt-6" data-login-submit>
                        <span data-login-label>Iniciar Sesión</span>
                    </x-ui.button>
                @endif

                <a href="{{ route('password.request') }}" class="mt-4 block text-center text-xs font-semibold text-brand hover:underline">
                    ¿Olvidaste tu contraseña?
                </a>
            </form>

            <footer class="mt-8 flex w-full max-w-[560px] flex-col items-center gap-3 border-t border-line pt-4 text-center text-[#9ca3af] lg:mt-0 lg:gap-4 lg:border-0 lg:pt-0">
                <div class="flex items-center gap-1.5 text-xs">
                    <x-ui.icon name="lock" :size="14" alt="" />
                    <span>Conexión segura • SSL/TLS</span>
                </div>
                <div class="space-y-1">
                    <p class="text-[11px]">Nube Empresarial v1.0.0</p>
                    <p class="text-[10px]">Uso exclusivo para personal autorizado del H. Ayuntamiento de Nezahualcóyotl</p>
                </div>
            </footer>
        </section>
    </main>
</x-layouts.auth>
