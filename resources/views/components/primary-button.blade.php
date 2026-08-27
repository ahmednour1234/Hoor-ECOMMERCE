<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary w-full']) }}>
    {{ $slot }}
</button>
