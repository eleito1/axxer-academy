@php($routePrefix = $routePrefix ?? 'admin')

<x-app-layout title="Módulo — Administração">
    <section class="card auth">
        <h1>{{ $module->exists ? 'Editar' : 'Novo' }} módulo</h1>
        <form method="POST" action="{{ $module->exists ? route($routePrefix.'.products.courses.modules.update', [$product, $course, $module]) : route($routePrefix.'.products.courses.modules.store', [$product, $course]) }}">
            @csrf
            @if($module->exists)
                @method('PUT')
            @endif
            <div class="field">
                <label>Título</label>
                <input name="title" value="{{ old('title', $module->title) }}" required>
            </div>
            <div class="field">
                <label>Descrição</label>
                <textarea name="description" rows="5" style="width:100%">{{ old('description', $module->description) }}</textarea>
            </div>
            <div class="field">
                <label>Ordem</label>
                <input type="number" min="0" name="order" value="{{ old('order', $module->order ?? 0) }}" required>
            </div>
            <button class="btn">Salvar</button>
        </form>
    </section>
</x-app-layout>
