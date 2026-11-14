@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-[#ded8cc] bg-[#fbfaf7] text-[#171615] shadow-sm placeholder:text-[#aaa397] focus:border-[#c99c42] focus:ring-[#c99c42]']) }}>
