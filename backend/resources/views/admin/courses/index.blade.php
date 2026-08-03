@php($routePrefix = $routePrefix ?? 'admin')

<x-app-layout title="Cursos — {{ $product->name }}">
    <div class="actions" style="justify-content:space-between">
        <div>
            <a href="{{ $routePrefix === 'creator' ? route('creator.dashboard') : route('admin.products.index') }}">← {{ $routePrefix === 'creator' ? 'Meus cursos' : 'Produtos' }}</a>
            <h1>Cursos de {{ $product->name }}</h1>
        </div>
        <a class="btn" href="{{ route($routePrefix.'.products.courses.create', $product) }}">Novo curso</a>
    </div>
    <section class="card">
        @forelse($courses as $course)
            <article class="user">
                <strong>#{{ $course->order }} · {{ $course->title }}</strong>
                <span class="badge {{ $course->published ? 'aprovado' : '' }}">{{ $course->published ? 'PUBLICADO' : 'RASCUNHO' }}</span>
                <p class="muted">{{ $course->modules_count }} módulos · /{{ $course->slug }}</p>
                <p class="muted">Criador: {{ $course->creator?->name ?? 'Sem criador responsável' }}</p>
                <div class="actions">
                    <a class="btn" href="{{ route($routePrefix.'.products.courses.modules.index', [$product, $course]) }}">Módulos</a>
                    <a class="btn secondary" href="{{ route($routePrefix.'.products.courses.edit', [$product, $course]) }}">Editar</a>
                    <form method="POST" action="{{ route($routePrefix.'.products.courses.destroy', [$product, $course]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn danger">Excluir</button>
                    </form>
                </div>
            </article>
        @empty
            <p>Nenhum curso cadastrado.</p>
        @endforelse
    </section>
</x-app-layout>
