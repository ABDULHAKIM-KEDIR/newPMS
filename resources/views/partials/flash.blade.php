@if (session('status') || session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="status-alert" role="alert">
        <div class="alert-content">
            <svg class="alert-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6L9 17l-5-5" />
            </svg>
            <span>{{ session('status') ?? session('success') }}</span>
        </div>
        <button type="button" @click="show = false" class="alert-close" aria-label="Close notification"
            title="Close">&times;</button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="form-alert alert-box"
        role="alert">
        <div class="alert-content">
            <svg class="alert-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" @click="show = false" class="alert-close" aria-label="Close alert"
            title="Close">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="form-alert alert-box"
        role="alert" style="align-items:flex-start;">
        <div style="flex:1;">
            <div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:4px;">
                <svg class="alert-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Please check the following errors:</span>
            </div>
            <ul style="margin:0; padding-left:26px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" @click="show = false" class="alert-close" aria-label="Close error list" title="Close"
            style="margin-top:2px;">&times;</button>
    </div>
@endif
