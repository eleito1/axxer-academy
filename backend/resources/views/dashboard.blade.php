@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
@endphp

<x-app-layout title="Academia — AXXER Academy">
    <div class="stack">
        <section class="hero-panel">
            <div class="spread">
                <div>
                    <p class="kicker">{{ $greeting }}</p>
                    <h1>{{ auth()->user()->name }}, continue evoluindo.</h1>
                    <p>Seu aprendizado fica salvo automaticamente para voce retomar sem atrito.</p>
                </div>
                <div class="soft-card" style="min-width: min(100%, 220px); background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.22)">
                    <span class="eyebrow" style="color:#d7e4ff">Progresso geral</span>
                    <strong style="display:block; margin: 6px 0 10px; font-size: 42px; line-height:1">{{ $general_percentage }}%</strong>
                    <x-ui.progress :value="$general_percentage" label="Progresso geral" />
                </div>
            </div>

            @if($last_progress)
                <div class="soft-card" style="margin-top: 24px; background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.18)">
                    <p class="eyebrow" style="color:#d7e4ff">Última aula</p>
                    <h2 style="font-size: clamp(24px, 6vw, 36px)">{{ $last_progress->lesson->title }}</h2>
                    <p>{{ $last_progress->lesson->module->course->title }} · posição {{ gmdate('i:s', $last_progress->last_seconds) }}</p>
                    <x-ui.button class="full-mobile" :href="route('academy.lessons.show', [$last_progress->lesson->module->course->product, $last_progress->lesson->module->course, $last_progress->lesson->module, $last_progress->lesson])">Continuar assistindo</x-ui.button>
                </div>
            @else
                <div class="soft-card" style="margin-top: 24px; background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.18)">
                    <p class="eyebrow" style="color:#d7e4ff">Comece por aqui</p>
                    <h2 style="font-size: clamp(24px, 6vw, 36px)">Escolha um curso disponivel</h2>
                    <p>Assim que abrir uma aula, ela aparecera aqui para retomada rapida.</p>
                </div>
            @endif
        </section>

        <section class="stack" aria-labelledby="available-courses-title">
            <x-ui.section-header eyebrow="Academia" title="Cursos disponíveis" id="available-courses-title" />
            <div class="grid cards auto">
                @forelse($available_courses as $item)
                    <article class="card course-card">
                        @if($item['course']->cover_image)
                            <img class="course-cover" src="{{ $item['course']->cover_image }}" alt="">
                        @else
                            <div class="course-art" aria-hidden="true">{{ mb_substr($item['course']->title, 0, 1) }}</div>
                        @endif
                        <div class="course-body">
                            <div>
                                <p class="eyebrow">{{ $item['course']->product->name }}</p>
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
                            <x-ui.button class="full" :href="route('academy.courses.show', [$item['course']->product, $item['course']])">{{ $item['percentage'] > 0 ? 'Continuar' : 'Começar curso' }}</x-ui.button>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state class="grid" title="Nenhum curso disponível" icon="0">
                        <p>Nenhum curso publicado foi liberado para sua conta ainda.</p>
                    </x-ui.empty-state>
                @endforelse
            </div>
        </section>

        <section class="stack" aria-labelledby="products-title">
            <x-ui.section-header eyebrow="Acessos" title="Meus produtos" id="products-title" />
            <div class="grid cards">
                @forelse($products as $product)
                    <article class="card">
                        <span class="badge aprovado">Acesso liberado</span>
                        <h3 style="margin-top: 16px">{{ $product->name }}</h3>
                        <p>{{ $product->description }}</p>
                        <x-ui.button variant="secondary" :href="route('academy.products.show', $product)">Explorar cursos</x-ui.button>
                    </article>
                @empty
                    <x-ui.empty-state title="Nenhum produto liberado" icon="AX">
                        <p>Quando um administrador liberar seu acesso, os produtos aparecerão aqui.</p>
                    </x-ui.empty-state>
                @endforelse
            </div>
        </section>

        <section class="stack" aria-labelledby="progress-title">
            <x-ui.section-header eyebrow="Progresso" title="Cursos em andamento" id="progress-title" />
            <div class="grid cards">
                @forelse($in_progress as $item)
                    <article class="card">
                        <p class="eyebrow">{{ $item['course']->product->name }}</p>
                        <h3>{{ $item['course']->title }}</h3>
                        <div class="spread">
                            <strong>{{ $item['percentage'] }}%</strong>
                            <span class="muted">{{ $item['completed'] }}/{{ $item['total'] }} aulas</span>
                        </div>
                        <x-ui.progress :value="$item['percentage']" :label="'Progresso de '.$item['course']->title" />
                        <div style="margin-top: 16px">
                            <x-ui.button :href="route('academy.courses.show', [$item['course']->product, $item['course']])">Continuar</x-ui.button>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state title="Nenhum curso iniciado" icon="▶">
                        <p>Abra uma aula para criar seu primeiro progresso.</p>
                    </x-ui.empty-state>
                @endforelse
            </div>
        </section>

        <section class="stack" aria-labelledby="completed-title">
            <x-ui.section-header eyebrow="Conquistas" title="Cursos concluídos" id="completed-title" />
            <div class="grid cards">
                @forelse($completed_courses as $item)
                    <article class="card">
                        <span class="badge aprovado">Certificado disponível</span>
                        <h3 style="margin-top: 16px">{{ $item['course']->title }}</h3>
                        <p>100% concluído</p>
                        <x-ui.button variant="secondary" :href="route('academy.courses.show', [$item['course']->product, $item['course']])">Rever curso</x-ui.button>
                    </article>
                @empty
                    <x-ui.empty-state title="Nenhum curso concluído ainda" icon="%">
                        <p>Seus certificados aparecerão aqui quando finalizar todos os módulos publicados.</p>
                    </x-ui.empty-state>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
