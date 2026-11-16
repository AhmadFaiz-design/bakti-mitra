@props(['active' => false])

<a {{ $attributes->class(['active' => $active]) }}>
    {{ $slot }}
</a>
