<?php
// Carry list filters with each edit form so separate tabs remain independent.
function factoryProductListContext(array $input): array
{
    $search = is_string($input['return_search'] ?? null) ? trim($input['return_search']) : '';
    $status = $input['return_status'] ?? 'All';
    if (!in_array($status, ['All', 'Active', 'Inactive'], true)) $status = 'All';
    return ['return_search' => $search, 'return_status' => $status];
}

function factoryProductListUrl(array $context): string
{
    return 'index.php?' . http_build_query([
        'search' => $context['return_search'], 'status' => $context['return_status'],
    ]);
}
