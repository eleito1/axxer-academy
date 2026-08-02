<x-app-layout title="{{ $course->title }} — AXXER Academy">
    <div class="stack">
        <x-ui.button variant="ghost" :href="route('academy.products.show', $product)">← {{ $product->name }}</x-ui.button>

        <section class="hero-panel">
            <p class="kicker">{{ $product->name }}</p>
            <h1>{{ $course->title }}</h1>
            <p>{{ $course->description }}</p>
        </section>

        <section class="stack" aria-labelledby="modules-title">
            <x-ui.section-header eyebrow="Conteúdo" title="Módulos do curso" id="modules-title" />
            @forelse($course->modules as $module)
                <article class="card">
                    <div class="spread">
                        <div>
                            <p class="eyebrow">Módulo {{ $module->order }}</p>
                            <h2>{{ $module->title }}</h2>
                            <p>{{ $module->description }}</p>
                        </div>
                        <x-ui.button variant="secondary" class="full-mobile" :href="route('academy.modules.show', [$product, $course, $module])">Ver módulo</x-ui.button>
                    </div>
                    <div class="stack" style="margin-top: 16px">
                        @foreach($module->lessons as $lesson)
                            <a class="soft-card spread" href="{{ route('academy.lessons.show', [$product, $course, $module, $lesson]) }}" style="text-decoration:none">
                                <strong>{{ $lesson->title }}</strong>
                                <span class="muted">{{ $lesson->duration ? gmdate('i:s', $lesson->duration) : '--:--' }}</span>
                            </a>
                        @endforeach
                    </div>
                </article>
            @empty
                <x-ui.empty-state title="Nenhum módulo disponível" icon="0">
                    <p>Este curso ainda não possui módulos publicados.</p>
                </x-ui.empty-state>
            @endforelse
        </section>
    </div>
</x-app-layout>
