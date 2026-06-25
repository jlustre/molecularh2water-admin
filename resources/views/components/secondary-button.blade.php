<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-teal-800 shadow-sm transition duration-150 ease-in-out hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
