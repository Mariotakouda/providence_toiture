@if (session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>
@endif

@if (session('warning'))
    <div class="mb-4 rounded-lg border border-orange-200 bg-orange-50 p-3 text-sm text-orange-700">{{ session('warning') }}</div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
@endif
