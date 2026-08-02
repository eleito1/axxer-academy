<x-app-layout title="{{ $product->name }} — AXXER Academy">
    <div class="stack">
        <x-ui.button variant="ghost" :href="route('dashboard')">← Meus produtos</x-ui.button>

        <section class="hero-panel">
            <p class="kicker">Produto</p>
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->description }}</p>
        </section>

        <section class="stack" aria-labelledby="product-courses-title">
            <x-ui.section-header eyebrow="Cursos" title="Escolha sua próxima aula" id="product-courses-title" />
            <div class="grid cards auto">
                @forelse($courses as $item)
                    <article class="card course-card">
                        @if($item['course']->cover_image)
                            <img class="course-cover" src="{{ $item['course']->cover_image }}" alt="">
                        @else
                            <div class="course-art" aria-hidden="true">{{ mb_substr($item['course']->title, 0, 1) }}</div>
                        @endif
                        <div class="course-body">
                            <div>
                                <h3>{{ $item['course']->title }}</h3>
                                <p>{{ $item['course']->description }}</p>
                            </div>
                            <div class="spread">
                                <strong>{{ $item['percentage'] }}%</strong>
                                <span class="muted">{{ $item['completed'] }}/{{ $item['total'] }} aulas</span>
                            </div>
                            <x-ui.progress :value="$item['percentage']" :label="'Progresso de '.$item['course']->title" />
                            @if($item['certificate_available'])
                                <span class="chip ok">Certificado disponível</span>
                            @endif
                            <x-ui.button class="full" :href="route('academy.courses.show', [$product, $item['course']])">{{ $item['percentage'] > 0 ? 'Continuar' : 'Começar curso' }}</x-ui.button>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state title="Nenhum curso publicado" icon="0">
                        <p>Este produto ainda não tem cursos publicados para alunos.</p>
                    </x-ui.empty-state>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
