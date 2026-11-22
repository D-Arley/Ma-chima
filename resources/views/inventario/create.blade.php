<x-app-layout>
    <x-slot name="header"><h1 class="font-serif text-3xl">Crear producto</h1></x-slot>
    <main class="mx-auto max-w-4xl px-5 py-10 sm:px-8">
        <form method="POST" action="{{ route('inventario.store') }}" enctype="multipart/form-data" class="grid gap-5 rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:p-8">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <div><x-input-label value="Categoría" /><select name="categoria_id" class="mt-2 w-full rounded-xl border-[#ded8cc] py-3" required>@foreach ($categorias as $categoria)<option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>@endforeach</select></div>
                <div class="rounded-xl bg-[#f8f6f0] px-4 py-3"><x-input-label value="Código de producto" /><p class="mt-2 text-sm text-[#716d65]">Se asigna automáticamente en secuencia al guardar.</p></div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><x-input-label value="Nombre" /><x-text-input name="nombre" class="mt-2 block w-full px-4 py-3" :value="old('nombre')" required /></div>
                <div><x-input-label value="Talla" /><x-text-input name="talla" class="mt-2 block w-full px-4 py-3" :value="old('talla')" required /></div>
            </div>
            <div><x-input-label value="Descripción" /><textarea name="descripcion" rows="3" class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3">{{ old('descripcion') }}</textarea></div>
            <div class="grid gap-5 sm:grid-cols-3">
                <div><x-input-label value="Costo COP" /><x-text-input name="costo" type="number" min="0" step="1" class="mt-2 block w-full px-4 py-3" :value="old('costo')" required /></div>
                <div><x-input-label value="Precio al público COP" /><x-text-input name="precio" type="number" min="0" step="1" class="mt-2 block w-full px-4 py-3" :value="old('precio')" required /></div>
                <div><x-input-label value="Stock inicial" /><x-text-input name="stock" type="number" min="0" class="mt-2 block w-full px-4 py-3" :value="old('stock', 0)" required /></div>
            </div>
            <div><x-input-label value="Imagen descriptiva" /><input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm"></div>
            @if ($errors->any())<div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            <div class="flex justify-end gap-3"><a href="{{ route('inventario.index') }}" class="rounded-full border border-[#d8cfbe] px-5 py-3 text-sm font-semibold">Cancelar</a><button class="rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Guardar producto</button></div>
        </form>
    </main>
</x-app-layout>
