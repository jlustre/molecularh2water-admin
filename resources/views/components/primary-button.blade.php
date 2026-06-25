<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-md border border-transparent bg-teal-400 px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition duration-150 ease-in-out hover:bg-teal-300 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-white active:bg-teal-500']) }}>
    {{ $slot }}
</button>
