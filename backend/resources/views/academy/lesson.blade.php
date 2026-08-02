<x-app-layout title="{{ $lesson->title }} — AXXER Academy">
    <style>
        .lesson-shell { display: grid; gap: 18px; }
        .lesson-back { width: fit-content; }
        .classroom { display: grid; gap: 18px; align-items: start; }
        .curriculum-panel {
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--surface);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .curriculum-panel summary {
            display: flex;
            min-height: 62px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            cursor: pointer;
            list-style: none;
            padding: 16px 18px;
            font-weight: 900;
        }
        .curriculum-panel summary::-webkit-details-marker { display: none; }
        .curriculum-panel summary::after { content: "+"; color: var(--brand); font-size: 22px; line-height: 1; }
        .curriculum-panel[open] summary::after { content: "−"; }
        .course-summary { display: grid; gap: 10px; padding: 0 18px 18px; }
        .module-title { padding: 16px 18px 8px; color: var(--muted); font-size: 12px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .lesson-link {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            border-left: 3px solid transparent;
            color: var(--text);
            padding: 13px 18px;
            text-decoration: none;
            transition: background .18s ease, border-color .18s ease, transform .18s ease;
        }
        .lesson-link:hover { background: var(--surface-soft); }
        .lesson-link.current { border-left-color: var(--brand); background: var(--brand-soft); }
        .lesson-state {
            display: grid;
            width: 28px;
            height: 28px;
            flex: none;
            place-items: center;
            color: var(--muted);
        }
        .lesson-state svg { display: block; width: 22px; height: 22px; }
        .lesson-link.completed .lesson-state { color: var(--ok); }
        .lesson-link.current .lesson-state { color: var(--brand); }
        .lesson-label strong, .lesson-label small { display: block; }
        .lesson-label strong { font-size: 14px; line-height: 1.25; }
        .lesson-label small { margin-top: 4px; color: var(--muted); font-size: 12px; }
        .player-card { padding: 12px; }
        .player {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-lg);
            background: #091329;
            aspect-ratio: 16 / 9;
            box-shadow: 0 18px 40px rgba(9, 19, 41, .18);
        }
        .player iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
        .lesson-info { display: grid; gap: 12px; padding: 18px 6px 6px; }
        .lesson-actions { display: grid; gap: 10px; margin-top: 8px; padding-top: 18px; border-top: 1px solid var(--line); }
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
            .classroom { grid-template-columns: minmax(280px, 330px) minmax(0, 1fr); gap: 22px; }
            .curriculum-panel { position: sticky; top: 86px; max-height: calc(100vh - 110px); overflow: auto; }
            .curriculum-panel summary { cursor: default; }
            .curriculum-panel summary::after { content: ""; }
            .lesson-actions { display: flex; align-items: center; }
            .lesson-actions .push { margin-left: auto; }
            .player-card { padding: 16px; }
        }
    </style>

    <div class="lesson-shell">
        <x-ui.button class="lesson-back" variant="ghost" :href="route('academy.products.show', $product)">← {{ $course->title }}</x-ui.button>

        <div class="classroom">
            <details class="curriculum-panel" open>
                <summary>
                    <span>Conteúdo do curso</span>
                    <span class="muted"><span id="progress-label">{{ $courseProgress['percentage'] }}%</span> concluído</span>
                </summary>
                <div class="course-summary">
                    <div>
                        <p class="eyebrow">Curso</p>
                        <h2 style="font-size: 22px">{{ $course->title }}</h2>
                    </div>
                    <div id="sidebar-progress" class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $courseProgress['percentage'] }}" aria-label="Progresso do curso">
                        <div class="progress-fill" style="width: {{ $courseProgress['percentage'] }}%"></div>
                    </div>
                </div>

                @foreach($sidebar as $group)
                    <div class="module-title">{{ $group['module']->title }}</div>
                    @foreach($group['lessons'] as $item)
                        <a class="lesson-link {{ $item['current'] ? 'current' : '' }} {{ $item['completed'] ? 'completed' : '' }}" href="{{ $item['url'] }}" @if($item['current']) aria-current="page" @endif>
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
                @endforeach
            </details>

            <section class="card player-card">
                <div class="player">
                    <iframe src="{{ $embedUrl }}" title="{{ $lesson->title }}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>

                <div class="lesson-info">
                    <div>
                        <p class="eyebrow">{{ $module->title }}</p>
                        <h1>{{ $lesson->title }}</h1>
                        <p>{{ $lesson->description }}</p>
                    </div>
                    <p class="watch-note">Sua posição é salva automaticamente enquanto esta página permanece aberta.</p>

                    @if($lesson->support_material)
                        <x-ui.button variant="secondary" :href="$lesson->support_material" target="_blank" rel="noopener noreferrer">Baixar material complementar</x-ui.button>
                    @endif

                    <div id="certificate" class="certificate" @if(!$courseProgress['certificate_available']) hidden @endif>Certificado disponível — curso 100% concluído</div>

                    <div class="lesson-actions">
                        <x-ui.button variant="secondary" class="{{ $previousUrl ? '' : 'disabled' }}" :href="$previousUrl ?? '#'">← Aula anterior</x-ui.button>
                        <button id="complete-button" class="btn" type="button" @disabled($progress->isCompleted())>{{ $progress->isCompleted() ? 'Aula concluída' : 'Marcar como concluída' }}</button>
                        <x-ui.button class="push {{ $nextUrl ? '' : 'disabled' }}" :href="$nextUrl ?? '#'">Próxima aula →</x-ui.button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const endpoint = @json($progressUrl);
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const button = document.getElementById('complete-button');
            const progressLabel = document.getElementById('progress-label');
            const progressTrack = document.getElementById('sidebar-progress');
            const progressFill = progressTrack.querySelector('.progress-fill');
            const certificate = document.getElementById('certificate');
            let seconds = {{ $progress->last_seconds }};
            let lastTick = Date.now();

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
