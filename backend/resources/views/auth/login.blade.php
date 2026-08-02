<x-app-layout title="Entrar — AXXER Academy">
    <section class="card auth">
        <p class="kicker">AXXER Academy</p>
        <h1>Entrar</h1>
        <p>Acesse seus treinamentos, continue aulas em andamento e acompanhe seu progresso.</p>

        @if($errors->any())
            <div class="errors" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Senha</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <label class="cluster" style="width: fit-content">
                <input type="checkbox" name="remember" value="1">
                <span>Manter conectado</span>
            </label>
            <div style="margin-top:18px">
                <button class="btn full" type="submit">Entrar</button>
            </div>
        </form>

        <div class="links">Ainda não possui acesso? <a href="{{ route('register') }}">Solicitar cadastro</a></div>
    </section>
</x-app-layout>
