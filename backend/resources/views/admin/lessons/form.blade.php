@php($routePrefix = $routePrefix ?? 'admin')
@php($videoMaxMb = config('videos.max_megabytes', 500))

<x-app-layout title="Aula — Administração">
    <style>
        .video-upload {
            display: grid;
            gap: 12px;
            border: 1px dashed var(--line);
            border-radius: var(--radius);
            background: color-mix(in srgb, var(--surface) 88%, var(--surface-soft));
            padding: 18px;
        }

        .video-upload.is-dragging {
            border-color: var(--brand);
            background: var(--brand-soft);
        }

        .video-upload input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .video-upload-picker {
            display: grid;
            gap: 8px;
            place-items: center;
            min-height: 138px;
            text-align: center;
        }

        .video-upload-picker strong {
            font-size: 18px;
            line-height: 1.2;
        }

        .upload-progress {
            display: grid;
            gap: 6px;
        }

        .upload-status {
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }
    </style>

    <section class="card auth">
        <h1>{{ $lesson->exists ? 'Editar' : 'Nova' }} aula</h1>
        <form id="lesson-form" method="POST" enctype="multipart/form-data" action="{{ $lesson->exists ? route($routePrefix.'.products.courses.modules.lessons.update', [$product, $course, $module, $lesson]) : route($routePrefix.'.products.courses.modules.lessons.store', [$product, $course, $module]) }}">
            @csrf
            @if($lesson->exists)
                @method('PUT')
            @endif
            <div class="field">
                <label>Título</label>
                <input name="title" value="{{ old('title', $lesson->title) }}" required>
            </div>
            <div class="field">
                <label>Descrição</label>
                <textarea name="description" rows="5" style="width:100%">{{ old('description', $lesson->description) }}</textarea>
            </div>
            <div class="field">
                <label>Escolher vídeo</label>
                <div class="video-upload" id="video-upload">
                    <input id="video-input" type="file" name="video" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm" @required(! $lesson->exists)>
                    <label class="video-upload-picker" for="video-input">
                        <strong>Arrastar vídeo</strong>
                        <span class="muted">ou</span>
                        <span class="btn secondary">Escolher arquivo</span>
                        <span class="field-help">MP4, MOV ou WEBM até {{ $videoMaxMb }} MB.</span>
                    </label>
                    <div class="upload-progress" aria-live="polite">
                        <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Progresso do upload">
                            <div id="upload-progress-fill" class="progress-fill" style="width:0%"></div>
                        </div>
                        <span id="upload-status" class="upload-status">
                            @if($lesson->exists && $lesson->video_original_name)
                                Vídeo atual: {{ $lesson->video_original_name }} · {{ number_format(($lesson->video_size ?? 0) / 1048576, 1, ',', '.') }} MB
                            @elseif($lesson->exists)
                                Vídeo atual mantido.
                            @else
                                Nenhum vídeo selecionado.
                            @endif
                        </span>
                    </div>
                </div>
                @error('video')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="grid">
                <div class="field">
                    <label>Duração em segundos</label>
                    <input type="number" min="0" name="duration" value="{{ old('duration', $lesson->duration) }}">
                </div>
                <div class="field">
                    <label>Ordem</label>
                    <input type="number" min="0" name="order" value="{{ old('order', $lesson->order ?? 0) }}" required>
                </div>
            </div>
            <div class="field">
                <label>URL do material complementar</label>
                <input type="url" name="support_material" value="{{ old('support_material', $lesson->support_material) }}">
            </div>
            <label><input type="checkbox" name="published" value="1" @checked(old('published', $lesson->published))> Publicada</label>
            <div style="margin-top:20px"><button id="lesson-submit" class="btn">Salvar</button></div>
        </form>
    </section>

    <script>
        (() => {
            const form = document.getElementById('lesson-form');
            const dropzone = document.getElementById('video-upload');
            const input = document.getElementById('video-input');
            const status = document.getElementById('upload-status');
            const fill = document.getElementById('upload-progress-fill');
            const progress = dropzone.querySelector('[role="progressbar"]');
            const button = document.getElementById('lesson-submit');

            function setUploading(uploading) {
                button.disabled = uploading;
                button.setAttribute('aria-busy', uploading ? 'true' : 'false');
            }

            function formatSize(bytes) {
                if (!bytes) return '0 MB';

                return (bytes / 1024 / 1024).toLocaleString('pt-BR', {maximumFractionDigits: 1}) + ' MB';
            }

            function updateFile(file) {
                if (!file) return;

                status.textContent = file.name + ' · ' + formatSize(file.size);
                fill.style.width = '0%';
                progress.setAttribute('aria-valuenow', '0');
            }

            input.addEventListener('change', () => updateFile(input.files[0]));

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragging');
                });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragging');
                });
            });

            dropzone.addEventListener('drop', (event) => {
                if (!event.dataTransfer.files.length) return;

                input.files = event.dataTransfer.files;
                updateFile(input.files[0]);
            });

            form.addEventListener('submit', (event) => {
                if (!input.files.length || !window.XMLHttpRequest) return;

                event.preventDefault();
                const request = new XMLHttpRequest();
                request.open(form.method, form.action);
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                request.setRequestHeader('Accept', 'application/json');
                setUploading(true);
                status.textContent = 'Enviando...';

                request.upload.addEventListener('progress', (progressEvent) => {
                    if (!progressEvent.lengthComputable) return;

                    const percentage = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                    fill.style.width = percentage + '%';
                    progress.setAttribute('aria-valuenow', percentage.toString());
                    status.textContent = percentage + '%';
                });

                request.addEventListener('load', () => {
                    if (request.status >= 200 && request.status < 400) {
                        fill.style.width = '100%';
                        progress.setAttribute('aria-valuenow', '100');
                        status.textContent = 'Upload concluído';
                        window.location.href = request.responseURL || form.action;

                        return;
                    }

                    status.textContent = 'Não foi possível concluir o upload. Revise o arquivo e tente novamente.';
                    setUploading(false);
                });

                request.addEventListener('error', () => {
                    status.textContent = 'Falha de conexão durante o upload.';
                    setUploading(false);
                });
                request.addEventListener('timeout', () => {
                    status.textContent = 'Tempo esgotado durante o upload.';
                    setUploading(false);
                });
                request.addEventListener('abort', () => {
                    status.textContent = 'Upload cancelado.';
                    setUploading(false);
                });
                request.send(new FormData(form));
            });
        })();
    </script>
</x-app-layout>
