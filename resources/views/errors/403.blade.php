<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">

                {{-- Ícone --}}
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-8 w-8 text-red-600"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v3m0 4h.01M5.455 4.546A9 9 0 1119.454 18.546 9 9 0 015.455 4.546z"/>
                    </svg>
                </div>

                {{-- Código --}}
                <p class="text-sm font-semibold tracking-wide text-red-600 uppercase">
                    Erro 403
                </p>

                {{-- Título --}}
                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    Acesso negado
                </h1>

                {{-- Mensagem --}}
                <p class="mt-3 text-sm text-gray-600">
                    Você não tem permissão para acessar esta funcionalidade.
                    <br>
                    Caso precise desse acesso, solicite a um administrador.
                </p>

                {{-- Ações --}}
                <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ url()->previous() }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300
                              px-4 py-2 text-sm font-medium text-gray-700
                              hover:bg-gray-100 transition">
                        Voltar
                    </a>

                    <a href="{{ route('dashboard.redirect') }}"
                       class="inline-flex items-center justify-center rounded-lg
                              bg-blue-600 px-4 py-2 text-sm font-medium text-white
                              hover:bg-blue-700 transition">
                        Ir para o Dashboard
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
