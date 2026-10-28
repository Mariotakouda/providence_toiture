<div>
    <label class="block text-xs text-muted">Période</label>
    <select name="period" class="rounded-lg border border-line px-3 py-2 text-sm">
        @foreach (\App\Services\ReportService::PERIODS as $key => $label)
            <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-xs text-muted">Du (période personnalisée)</label>
    <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="rounded-lg border border-line px-3 py-2 text-sm">
</div>
<div>
    <label class="block text-xs text-muted">Au</label>
    <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="rounded-lg border border-line px-3 py-2 text-sm">
</div>
