<x-layouts.auth title="Recuperar contraseña">
    <main class="flex min-h-screen items-center justify-center bg-canvas px-5 py-10">
        <section class="w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-sm sm:p-8">
            <div class="mb-8 flex items-center gap-2">
                <x-ui.brand-logo alt="" width="48" height="32" class="h-8 w-12 rounded-md" />
                <span class="text-xs font-bold text-muted">NUBE Municipal</span>
            </div>

            <h1 class="text-2xl font-bold text-brand dark:text-white">Recuperar contraseña</h1>
            <p class="mt-2 text-sm leading-6 text-muted">Te enviaremos desde el sistema de accesos un enlace para definir una nueva contraseña.</p>

            @if (session('status'))
                <x-ui.alert tone="success" class="mt-5">{{ session('status') }}</x-ui.alert>
            @endif
            @if (session('auth_error'))
                <x-ui.alert class="mt-5">{{ session('auth_error') }}</x-ui.alert>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="mt-6">
                @csrf
                <x-ui.input
                    label="Correo electrónico"
                    name="email"
                    type="email"
                    autocomplete="email"
                    placeholder="nombre@nezahualcoyotl.gob.mx"
                    :value="old('email')"
                    :error="$errors->first('email')"
                    required
                    autofocus
                />
                <x-ui.button type="submit" class="mt-6 h-12 w-full">Enviar enlace</x-ui.button>
            </form>

            <a href="{{ route('login') }}" class="mt-5 block text-center text-xs font-semibold text-brand dark:text-white">Volver al inicio de sesión</a>
        </section>
    </main>
</x-layouts.auth>
