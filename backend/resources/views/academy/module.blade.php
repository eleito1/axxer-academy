<x-app-layout title="{{ $module->title }} — AXXER Academy">
    <div class="stack">
        <x-ui.button variant="ghost" :href="route('academy.courses.show', [$product, $course])">← {{ $course->title }}</x-ui.button>

        <section class="hero-panel">
            <p class="kicker">Módulo</p>
            <h1>{{ $module->title }}</h1>
            <p>{{ $module->description }}</p>
        </section>

        <section class="stack" aria-labelledby="module-lessons-title">
            <x-ui.section-header eyebrow="Aulas" title="Continue no seu ritmo" id="module-lessons-title" />
            @forelse($module->lessons as $lesson)
                <article class="card">
                    <div class="spread">
                        <div>
                            <h2>{{ $lesson->title }}</h2>
                            <p>{{ $lesson->description }}</p>
                            <span class="muted">{{ $lesson->duration ? gmdate('i:s', $lesson->duration) : '--:--' }}</span>
                        </div>
                        <x-ui.button class="full-mobile" :href="route('academy.lessons.show', [$product, $course, $module, $lesson])">Assistir aula</x-ui.button>
                    </div>
                </article>
            @empty
                <x-ui.empty-state title="Nenhuma aula publicada" icon="0">
                    <p>Quando novas aulas forem publicadas, elas aparecerão neste módulo.</p>
                </x-ui.empty-state>
            @endforelse
        </section>
    </div>
</x-app-layout>
