<x-app-layout>
    <x-slot name="header"><h1 class="font-serif text-3xl">Editar {{ $producto->nombre }}</h1></x-slot>
    <main class="mx-auto max-w-5xl px-5 py-10 sm:px-8">
        <form x-data="{
            costo: {{ (float) $producto->costo }},
            precio: {{ (float) $producto->precio }},
            get margen() { return (this.precio / 1.19) - this.costo; }
        }" method="POST" action="{{ route('inventario.update', $producto) }}" enctype="multipart/form-data" class="grid gap-6 rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:p-8">
            @csrf
            @method('PATCH')
            <div class="grid gap-6 lg:grid-cols-[170px_1fr]">
                <div>
                    <img src="{{ $producto->imagen ?: asset('images/ma-chima-logo.png') }}" alt="{{ $producto->nombre }}" class="aspect-square w-full rounded-2xl object-cover">
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="mt-3 w-full text-xs">
                </div>
                <div class="grid gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><x-input-label value="Categoría" /><select name="categoria_id" class="mt-2 w-full rounded-xl border-[#ded8cc] py-3">@foreach ($categorias as $categoria)<option value="{{ $categoria->id }}" @selected($producto->categoria_id === $categoria->id)>{{ $categoria->nombre }}</option>@endforeach</select></div>
                        <div class="rounded-xl bg-[#f8f6f0] px-4 py-3"><x-input-label value="Código de producto" /><p class="mt-2 font-mono text-sm font-bold">{{ $producto->codigo }}</p><p class="mt-1 text-xs text-[#716d65]">Código automático; no editable.</p></div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><x-input-label value="Nombre" /><x-text-input name="nombre" class="mt-2 block w-full px-4 py-3" :value="$producto->nombre" required /></div>
                        <div><x-input-label value="Talla" /><x-text-input name="talla" class="mt-2 block w-full px-4 py-3" :value="$producto->talla" required /></div>
                    </div>
                    <div><x-input-label value="Descripción" /><textarea name="descripcion" rows="3" class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3">{{ old('descripcion', $producto->descripcion) }}</textarea></div>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <x-input-label value="Costo COP" />
                    <x-text-input name="costo" type="number" min="0" step="1" x-model.number="costo" class="mt-2 block w-full px-4 py-3" :value="$producto->costo" required />
                </div>
                <div>
                    <x-input-label value="Precio público COP" />
                    <x-text-input name="precio" type="number" min="0" step="1" x-model.number="precio" class="mt-2 block w-full px-4 py-3" :value="$producto->precio" required />
                </div>
                <div class="rounded-xl border border-[#e7d8b5] bg-[#fbf6e9] p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#8d6929]">Margen neto estimado</p>
                    <p class="mt-2 font-serif text-2xl" x-text="'$' + Math.round(margen).toLocaleString('es-CO')"></p>
                    <p class="mt-1 text-xs text-[#716d65]">IVA descontado del precio</p>
                </div>
            </div>

            <div class="rounded-2xl bg-[#f8f6f0] p-5">
                <h2 class="font-serif text-xl">Ajustar existencias</h2>
                <p class="mt-1 text-sm text-[#716d65]">Stock actual: <strong>{{ $producto->stock }}</strong>. Use positivo para ingresar y negativo para descontar.</p>
                <div class="mt-5 grid gap-5 sm:grid-cols-3">
                    <div><x-input-label value="Cantidad (+ / -)" /><x-text-input name="ajuste" type="number" class="mt-2 block w-full px-4 py-3" :value="0" required /></div>
                    <div>
                        <x-input-label value="Motivo del descuento" />
                        <select name="motivo" class="mt-2 w-full rounded-xl border-[#ded8cc] py-3">
                            <option value="">Sin ajuste o ingreso</option>
                            <option value="Dano">Daño</option>
                            <option value="Perdida">Pérdida</option>
                            <option value="Correccion">Corrección</option>
                            <option value="Garantia">Garantía</option>
                        </select>
                    </div>
                    <div><x-input-label value="Explicación" /><textarea name="explicacion" rows="2" class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3" placeholder="Explique el movimiento"></textarea></div>
                </div>
            </div>

            @if ($errors->any())<div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            <div class="flex justify-end gap-3">
                <a href="{{ route('inventario.index') }}" class="rounded-full border border-[#d8cfbe] px-5 py-3 text-sm font-semibold">Cancelar</a>
                <button class="rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Guardar cambios</button>
            </div>
        </form>
    </main>
</x-app-layout>
