@php
    $failure = \App\DatabaseUpdate::isNeeded() ? \App\DatabaseUpdate::failure() : null;
@endphp
@if ($failure)
    <div role="alert" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid var(--warning-300, #fcd34d); background: var(--warning-50, #fffbeb); color: var(--warning-900, #78350f); font-size: 0.875rem; line-height: 1.5;">
        <strong>The database couldn't be updated for this version of Light CMS.</strong>
        The site keeps working as before, but saving pages and settings may fail until it's done.
        It's tried again automatically every {{ \App\DatabaseUpdate::RETRY_AFTER_MINUTES }} minutes;
        the last try was {{ $failure['at']->diffForHumans() }}.
        <br>
        <span style="opacity: 0.8;">Reason: {{ $failure['message'] }} (SQLite {{ \App\DatabaseCopy::sqliteVersion() }})</span>
    </div>
@endif
