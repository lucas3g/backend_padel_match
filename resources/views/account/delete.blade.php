<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-800">Excluir Conta</h1>
        <p class="mt-2 text-sm text-gray-600">
            Ao confirmar, sua conta e todos os seus dados serão permanentemente excluídos.
            Esta ação <strong>não pode ser desfeita</strong>.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('account.delete') }}">
        @csrf
        @method('DELETE')

        <div class="mb-4">
            <x-input-label for="email" value="E-mail" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email')"
                required
                autofocus
            />
        </div>

        <div class="mb-6">
            <x-input-label for="password" value="Senha" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-1 block w-full"
                required
            />
        </div>

        <div class="mb-6 flex items-start gap-2">
            <input
                id="confirm"
                name="confirm"
                type="checkbox"
                class="mt-1 rounded border-gray-300 text-red-600"
                required
                onclick="document.getElementById('submit-btn').disabled = !this.checked"
            />
            <label for="confirm" class="text-sm text-gray-600">
                Entendo que todos os meus dados serão excluídos permanentemente.
            </label>
        </div>

        <x-danger-button id="submit-btn" class="w-full justify-center" disabled>
            Excluir minha conta
        </x-danger-button>
    </form>
</x-guest-layout>
