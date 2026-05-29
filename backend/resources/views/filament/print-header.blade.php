@php
    $routeName = optional(request()->route())->getName();
    $raw = $routeName ?: request()->path();
    // Try to derive a friendly page title from Filament route name like:
    // filament.admin.resources.users.index => Users
    $page = str_replace([
        'filament.', 'admin.', 'resources.', 'pages.', 'widgets.', '.index', '.list', '.create', '.edit'
    ], '', $raw);
    $page = str_replace(['.', '-', '/'], ' ', $page);
    $page = \Illuminate\Support\Str::title(trim($page));
    $app = config('app.name', 'Cooperative');
@endphp

<div class="print-only" style="padding: 0 0 12px; margin: 0 0 12px; border-bottom: 1px solid #000;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap: 12px;">
        <div>
            <div style="font-size: 18px; font-weight: 700;">{{ $app }} â€” Admin</div>
            <div style="font-size: 14px;">{{ $page ?: 'Listing' }}</div>
        </div>
        <div style="text-align: right; font-size: 12px; line-height: 1.2;">
            <div>Printed: {{ now()->format('Y-m-d H:i') }}</div>
            <div>URL: {{ request()->fullUrl() }}</div>
        </div>
    </div>
</div>
