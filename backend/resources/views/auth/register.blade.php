<x-app-layout title="Cadastro — AXXER Academy" :show-errors="false">
    <section class="card auth">
        <h1>Solicitar acesso</h1>
        <p>Após o cadastro, um administrador analisará sua solicitação.</p>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div class="grid">
                <div class="field">
                    <label for="name">Nome</label>
                    <input id="name" name="name" value="{{ old('name') }}" required @error('name') aria-invalid="true" @enderror>
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="company">Empresa</label>
                    <input id="company" name="company" value="{{ old('company') }}" required @error('company') aria-invalid="true" @enderror>
                    @error('company') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="city">Cidade</label>
                    <input id="city" name="city" value="{{ old('city') }}" required @error('city') aria-invalid="true" @enderror>
                    @error('city') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="whatsapp">WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required @error('whatsapp') aria-invalid="true" @enderror>
                    @error('whatsapp') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="field">
                <label for="interested_product_id">Produto de interesse</label>
                <select id="interested_product_id" name="interested_product_id" required @error('interested_product_id') aria-invalid="true" @enderror>
                    <option value="">Selecione</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('interested_product_id') == $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('interested_product_id') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required @error('email') aria-invalid="true" @enderror>
                @error('email') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="grid">
                <div class="field">
                    <label for="password">Senha</label>
                    <input id="password" type="password" name="password" required aria-describedby="password-help" @error('password') aria-invalid="true" @enderror>
                    <span id="password-help" class="field-help">Mínimo de 8 caracteres.</span>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirmar senha</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required @error('password_confirmation') aria-invalid="true" @enderror>
                    @error('password_confirmation') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <button class="btn">Enviar solicitação</button>
        </form>

        <div class="links"><a href="{{ route('login') }}">Voltar para o login</a></div>
    </section>
</x-app-layout>
