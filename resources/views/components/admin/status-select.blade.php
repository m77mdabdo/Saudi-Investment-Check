@props(['lead', 'statuses'])

<form method="POST" action="{{ route('admin.leads.status', $lead) }}" class="inline-flex">
    @csrf
    @method('PATCH')
    <select name="sales_status" class="ad-select h-9 min-h-9 w-auto py-1 text-xs font-bold" onchange="this.form.submit()" aria-label="حالة المبيعات">
        @foreach ($statuses as $status)
            <option value="{{ $status->key }}" @selected($lead->sales_status === $status->key)>{{ $status->label }}</option>
        @endforeach
    </select>
</form>
