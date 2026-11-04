{{-- Hints such as "Auto: 36 px" say what's in use, so they need to meet the 4.5:1 contrast standard. --}}
<style>
    ::placeholder,
    .fi-select-input-placeholder,
    select.fi-select-input:has(> option[value=""]:checked) {
        color: var(--gray-500);
        opacity: 1;
    }

    .dark ::placeholder,
    .dark .fi-select-input-placeholder,
    .dark select.fi-select-input:has(> option[value=""]:checked) {
        color: var(--gray-400);
    }

    select.fi-select-input:has(> option[value=""]:checked) > option {
        color: var(--gray-950);
    }

    .dark select.fi-select-input:has(> option[value=""]:checked) > option {
        color: #fff;
    }
</style>
