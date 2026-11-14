<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full border border-transparent bg-[#171511] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#302a20] focus:outline-none focus:ring-2 focus:ring-[#c99c42] focus:ring-offset-2 active:bg-black']) }}>
    {{ $slot }}
</button>
