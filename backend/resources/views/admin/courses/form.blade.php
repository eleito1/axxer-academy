@php($routePrefix = $routePrefix ?? 'admin')

<x-app-layout title="Curso — Administração">
    <section class="card auth">
        <h1>{{ $course->exists ? 'Editar' : 'Novo' }} curso</h1>
        <form method="POST" action="{{ $course->exists ? route($routePrefix.'.products.courses.update', [$product, $course]) : route($routePrefix.'.products.courses.store', $product) }}">
            @csrf
            @if($course->exists)
                @method('PUT')
            @endif

            <div class="field">
                <label>Produto</label>
                <p class="muted">{{ $product->name }}</p>
            </div>

            @if(auth()->user()->isAdmin())
                <div class="field">
                    <label for="creator_id">Criador</label>
                    <select id="creator_id" name="creator_id">
                        <option value="">Sem criador responsável</option>
                        @foreach($creators as $creator)
                            <option value="{{ $creator->id }}" @selected((string) old('creator_id', $course->creator_id) === (string) $creator->id)>{{ $creator->name }} · {{ $creator->email }}</option>
                        @endforeach
                    </select>
                    <small class="field-help">Somente usuários com papel Criador podem ser selecionados.</small>
                </div>
            @else
                <div class="field">
                    <label>Criador</label>
                    <p class="muted">{{ auth()->user()->name }}</p>
                </div>
            @endif

            <div class="field">
                <label>Título</label>
                <input name="title" value="{{ old('title', $course->title) }}" required>
            </div>
            <div class="field">
                <label>Slug</label>
                <input name="slug" value="{{ old('slug', $course->slug) }}" required>
            </div>
            <div class="field">
                <label>Descrição</label>
                <textarea name="description" rows="4" style="width:100%">{{ old('description', $course->description) }}</textarea>
            </div>
            <div class="field">
                <label>URL da capa</label>
                <input type="url" name="cover_image" value="{{ old('cover_image', $course->cover_image) }}">
            </div>
            <div class="field">
                <label>Ordem</label>
                <input type="number" min="0" name="order" value="{{ old('order', $course->order ?? 0) }}" required>
            </div>
            <label><input type="checkbox" name="published" value="1" @checked(old('published', $course->published))> Publicado</label>
            <div style="margin-top:20px"><button class="btn">Salvar</button></div>
        </form>
    </section>
</x-app-layout>
