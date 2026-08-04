@php
    $direction = request('sort') === $field && request('direction') === 'asc' ? 'desc' : 'asc';
    $isActive = request('sort', 'name') === $field;
@endphp

<a href="{{ request()->fullUrlWithQuery(['sort' => $field, 'direction' => $direction]) }}"
    class="inline-flex items-center gap-1 hover:text-gray-700">
    {{ $label }}
    @if ($isActive)
        <span class="text-gray-400">{{ request('direction', 'asc') === 'asc' ? '↑' : '↓' }}</span>
    @endif
</a>
