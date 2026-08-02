<x-app-layout title="Status da conta — AXXER Academy">
    <section class="card auth">
        <p class="kicker">Status da conta</p>
        <h1>{{ auth()->user()->status->value === 'pendente' ? 'Estamos analisando seu acesso.' : 'Seu acesso está bloqueado.' }}</h1>
        @if(auth()->user()->status->value === 'pendente')
            <span class="badge">Pendente</span>
            <p style="margin-top: 16px">Seu cadastro foi recebido e aguarda aprovação do administrador. Você terá acesso aos produtos assim que a análise for concluída.</p>
        @else
            <span class="badge bloqueado">Bloqueado</span>
            <p style="margin-top: 16px">Entre em contato com o suporte da AXXER para mais informações.</p>
        @endif
    </section>
</x-app-layout>
