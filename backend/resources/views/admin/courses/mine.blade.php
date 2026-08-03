<x-app-layout title="Criador — AXXER Academy">
    <div class="actions" style="justify-content:space-between">
        <div>
            <h1>Meus cursos</h1>
            <p>Gerencie apenas os cursos em que você é o criador responsável.</p>
        </div>
    </div>

    <section class="card" style="margin-bottom:20px">
        <h2>Novo curso</h2>
        <p>Escolha o produto onde o curso será criado.</p>
        <div class="actions">
            @forelse($products as $product)
                <a class="btn secondary" href="{{ route('creator.products.courses.create', $product) }}">{{ $product->name }}</a>
            @empty
                <p>Nenhum produto ativo disponível.</p>
            @endforelse
        </div>
    </section>

    <section class="card">
        <h2>Cursos próprios</h2>
        @forelse($courses as $course)
            <article class="user">
                <strong>#{{ $course->order }} · {{ $course->title }}</strong>
                <span class="badge {{ $course->published ? 'aprovado' : '' }}">{{ $course->published ? 'PUBLICADO' : 'RASCUNHO' }}</span>
                <p class="muted">{{ $course->product->name }} · {{ $course->modules_count }} módulos · /{{ $course->slug }}</p>
                <p class="muted">Criador: {{ $course->creator?->name ?? 'Sem criador responsável' }}</p>
                <div class="actions">
                    <a class="btn" href="{{ route('creator.products.courses.modules.index', [$course->product, $course]) }}">Módulos</a>
                    <a class="btn secondary" href="{{ route('creator.products.courses.edit', [$course->product, $course]) }}">Editar</a>
                </div>
            </article>
        @empty
            <p>Nenhum curso próprio cadastrado.</p>
        @endforelse
    </section>
</x-app-layout>
