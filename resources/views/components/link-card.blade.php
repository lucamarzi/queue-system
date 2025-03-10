@props([
    'route', 
    'title', 
    'description', 
    'color' => 'blue'
])

<a href="{{ $route }}" target="_blank" {{ $attributes->merge(['class' => 'link-card bg-white rounded-lg shadow-md overflow-hidden']) }}>
    <div class="bg-{{ $color }}-500 text-white p-4">
        <h2 class="text-2xl font-bold">{{ $title }}</h2>
    </div>
    <div class="p-6">
        <p class="text-gray-600">{{ $description }}</p>
    </div>
</a>