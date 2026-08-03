@php($routePrefix = $routePrefix ?? 'admin')

<x-app-layout title="Módulos — {{ $course->title }}">
    <div class="actions" style="justify-content:space-between">
        <div>
            <a href="{{ route($routePrefix.'.products.courses.index', $product) }}">← Cursos</a>
            <h1>Módulos de {{ $course->title }}</h1>
        </div>
        <a class="btn" href="{{ route($routePrefix.'.products.courses.modules.create', [$product, $course]) }}">Novo módulo</a>
    </div>
    <section class="card">
        @forelse($modules as $module)
            <article class="user">
                <strong>#{{ $module->order }} · {{ $module->title }}</strong>
                <p class="muted">{{ $module->lessons_count }} aulas</p>
                <div class="actions">
                    <a class="btn" href="{{ route($routePrefix.'.products.courses.modules.lessons.index', [$product, $course, $module]) }}">Aulas</a>
                    <a class="btn secondary" href="{{ route($routePrefix.'.products.courses.modules.edit', [$product, $course, $module]) }}">Editar</a>
                    <form method="POST" action="{{ route($routePrefix.'.products.courses.modules.destroy', [$product, $course, $module]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn danger">Excluir</button>
                    </form>
                </div>
            </article>
        @empty
            <p>Nenhum módulo cadastrado.</p>
        @endforelse
    </section>
</x-app-layout>
