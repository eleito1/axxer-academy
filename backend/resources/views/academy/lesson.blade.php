<x-app-layout title="{{ $lesson->title }} — AXXER Academy">
    <style>
        .lesson-shell { display: grid; gap: 16px; }
        .lesson-back { width: fit-content; }
        .classroom { display: grid; grid-template-areas: "player" "curriculum"; gap: 16px; align-items: start; }
        .curriculum-panel {
            grid-area: curriculum;
            display: grid;
            gap: 8px;
            border-top: 1px solid var(--line);
            padding-top: 14px;
        }
        .curriculum-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
        }
        .curriculum-head h2 { margin-bottom: 0; font-size: 18px; }
        .module-title { padding: 10px 0 2px; color: var(--muted); font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .lesson-list { display: grid; }
        .lesson-link {
            display: flex;
            gap: 8px;
            align-items: center;
            border-top: 1px solid var(--line);
            border-left: 0;
            color: var(--text);
            min-height: 36px;
            padding: 5px 0;
            text-decoration: none;
            transition: background .18s ease, border-color .18s ease;
        }
        .lesson-link:hover { background: color-mix(in srgb, var(--surface-soft) 56%, transparent); }
        .lesson-link.current { color: var(--brand); }
        .lesson-state {
            display: grid;
            width: 20px;
            height: 20px;
            flex: none;
            place-items: center;
            color: var(--muted);
        }
        .lesson-state svg { display: block; width: 17px; height: 17px; }
        .lesson-link.completed .lesson-state { color: var(--ok); }
        .lesson-link.current .lesson-state { color: var(--brand); }
        .lesson-label strong, .lesson-label small { display: block; }
        .lesson-label strong { font-size: 12px; line-height: 1.15; }
        .lesson-label small { margin-top: 1px; color: var(--muted); font-size: 10px; }
        .player-card { grid-area: player; padding: 0; border: 0; box-shadow: none; background: transparent; }
        .player {
            position: relative;
            overflow: visible;
            border-radius: 0;
            border: 1px solid var(--line);
            background: var(--surface);
            aspect-ratio: 16 / 9;
        }
        .player.drive-player { min-height: clamp(224px, 58vw, 260px); }
        .player iframe {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
            background: var(--surface);
        }
        .lesson-info { display: grid; gap: 10px; padding: 18px 0 0; }
        .lesson-actions { display: grid; gap: 7px; margin-top: 2px; padding-top: 12px; border-top: 1px solid var(--line); }
        .lesson-actions .btn { min-height: 36px; padding: 7px 12px; font-size: 13px; }
        .lesson-progress { display: grid; gap: 6px; padding-top: 2px; }
        .lesson-progress strong { font-size: 13px; }
        .lesson-status { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .certificate {
            border: 1px solid color-mix(in srgb, var(--ok) 28%, var(--ok-soft));
            border-radius: var(--radius);
            background: var(--ok-soft);
            color: var(--ok);
            padding: 14px 16px;
            font-weight: 900;
        }
        .watch-note { color: var(--muted); font-size: 13px; }

        @media (min-width: 900px) {
            .classroom { grid-template-areas: "curriculum player"; grid-template-columns: minmax(260px, 310px) minmax(0, 1fr); gap: 22px; }
            .curriculum-panel {
                position: sticky;
                top: 86px;
                max-height: calc(100vh - 110px);
                overflow: auto;
                border-top: 0;
                border-right: 1px solid var(--line);
                padding: 4px 18px 0 0;
            }
            .player.drive-player { min-height: 0; }
            .lesson-actions { display: flex; align-items: center; }
            .lesson-actions .push { margin-left: auto; }
        }
    </style>

    <div class="lesson-shell">
        <x-ui.button class="lesson-back" variant="ghost" :href="route('academy.products.show', $product)">← {{ $course->title }}</x-ui.button>

        <div class="classroom">
            <section id="lesson-player-card" class="card player-card" tabindex="-1" aria-labelledby="lesson-title">
                @php($isDrivePlayer = str_contains($embedUrl, 'drive.google.com'))
                <div class="player {{ $isDrivePlayer ? 'drive-player' : '' }}" id="lesson-player" data-provider="{{ $isDrivePlayer ? 'google-drive' : 'standard' }}">
                    <iframe src="{{ $embedUrl }}" title="{{ $lesson->title }}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="eager" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>

                <div class="lesson-info">
                    <div>
                        <p class="eyebrow">{{ $module->title }}</p>
                        <h1 id="lesson-title">{{ $lesson->title }}</h1>
                        <p>{{ $lesson->description }}</p>
                    </div>
                    <div class="lesson-status">
                        <span class="eyebrow">Status</span>
                        <span class="chip {{ $progress->isCompleted() ? 'ok' : '' }}">{{ $progress->isCompleted() ? 'Aula concluída' : 'Em andamento' }}</span>
                    </div>
                    <p class="watch-note">Sua posição é salva automaticamente enquanto esta página permanece aberta.</p>

                    @if($lesson->support_material)
                        <x-ui.button variant="secondary" :href="$lesson->support_material" target="_blank" rel="noopener noreferrer">Baixar material complementar</x-ui.button>
                    @endif

                    <div id="certificate" class="certificate" @if(!$courseProgress['certificate_available']) hidden @endif>Certificado disponível — curso 100% concluído</div>

                    <div class="lesson-actions" aria-label="Navegação da aula">
                        <x-ui.button variant="secondary" class="{{ $previousUrl ? '' : 'disabled' }}" :href="$previousUrl ?? '#'">← Aula anterior</x-ui.button>
                        <button id="complete-button" class="btn" type="button" @disabled($progress->isCompleted())>{{ $progress->isCompleted() ? 'Aula concluída' : 'Marcar como concluída' }}</button>
                        <x-ui.button class="push {{ $nextUrl ? '' : 'disabled' }}" :href="$nextUrl ?? '#'">Próxima aula →</x-ui.button>
                    </div>

                    <div class="lesson-progress">
                        <div id="course-progress" class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $courseProgress['percentage'] }}" aria-label="Progresso do curso">
                            <div class="progress-fill" style="width: {{ $courseProgress['percentage'] }}%"></div>
                        </div>
                        <strong><span id="progress-label">{{ $courseProgress['percentage'] }}%</span> concluído</strong>
                    </div>
                </div>
            </section>

            <section class="curriculum-panel" id="course-curriculum" aria-labelledby="course-curriculum-title">
                <div class="curriculum-head">
                    <h2 id="course-curriculum-title">Conteúdo da aula</h2>
                    <span class="muted">{{ $courseProgress['completed'] }}/{{ $courseProgress['total'] }} aulas</span>
                </div>
                @foreach($sidebar as $group)
                    <div class="module-title">{{ $group['module']->title }}</div>
                    <div class="lesson-list">
                        @foreach($group['lessons'] as $item)
                            <a class="lesson-link {{ $item['current'] ? 'current' : '' }} {{ $item['completed'] ? 'completed' : '' }}" href="{{ $item['url'] }}?focus=player" @if($item['current']) aria-current="page" @endif>
                                <span class="lesson-state">
                                    @if($item['current'])
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="currentColor"/><path d="M10 8.5v7l5.5-3.5z" fill="white"/></svg>
                                        <span class="sr-only">Aula atual:</span>
                                    @elseif($item['completed'])
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="currentColor"/><path d="m7 12.2 3.1 3.1L17.5 8" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span class="sr-only">Aula concluída:</span>
                                    @else
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5.5" width="14" height="13" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m17 10 4-2v8l-4-2z" fill="currentColor"/></svg>
                                        <span class="sr-only">Aula não iniciada:</span>
                                    @endif
                                </span>
                                <span class="lesson-label">
                                    <strong>{{ $item['lesson']->title }}</strong>
                                    <small>{{ $item['duration'] }}</small>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </section>
        </div>
    </div>

    <script>
        (() => {
            const endpoint = @json($progressUrl);
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const button = document.getElementById('complete-button');
            const progressLabel = document.getElementById('progress-label');
            const progressTrack = document.getElementById('course-progress');
            const progressFill = progressTrack.querySelector('.progress-fill');
            const certificate = document.getElementById('certificate');
            const playerCard = document.getElementById('lesson-player-card');
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            let seconds = {{ $progress->last_seconds }};
            let lastTick = Date.now();

            function scrollToPlayerFromLessonList() {
                const url = new URL(window.location.href);
                if (url.searchParams.get('focus') !== 'player') return;

                const header = document.querySelector('.top');
                const offset = (header ? header.getBoundingClientRect().height : 0) + 14;
                const top = playerCard.getBoundingClientRect().top + window.pageYOffset - offset;

                window.scrollTo({top: Math.max(0, top), behavior: reducedMotion.matches ? 'auto' : 'smooth'});
                url.searchParams.delete('focus');
                window.history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') + url.hash);
            }

            function schedulePlayerFocus() {
                requestAnimationFrame(scrollToPlayerFromLessonList);
            }

            if (document.readyState === 'complete') {
                schedulePlayerFocus();
            } else {
                window.addEventListener('load', schedulePlayerFocus, {once: true});
            }

            async function save(done = false, keepalive = false) {
                if (document.visibilityState === 'visible') {
                    seconds += Math.max(0, Math.round((Date.now() - lastTick) / 1000));
                }
                lastTick = Date.now();
                if (done) {
                    button.setAttribute('aria-busy', 'true');
                }
                try {
                    const response = await fetch(endpoint, {
                        method: 'PUT',
                        keepalive,
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
                        body: JSON.stringify({last_seconds: seconds, completed: done}),
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    if (data.completed) {
                        button.textContent = 'Aula concluída';
                        button.disabled = true;
                    }
                    progressLabel.textContent = data.percentage + '%';
                    progressTrack.setAttribute('aria-valuenow', data.percentage);
                    progressFill.style.width = data.percentage + '%';
                    if (data.certificate_available) certificate.hidden = false;
                } catch (error) {
                } finally {
                    button.removeAttribute('aria-busy');
                }
            }

            button.addEventListener('click', () => save(true));
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') save(false, true);
                else lastTick = Date.now();
            });
            setInterval(() => {
                if (document.visibilityState === 'visible') save();
            }, 15000);
            window.addEventListener('beforeunload', () => save(false, true));
        })();
    </script>
</x-app-layout>
