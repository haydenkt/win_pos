<?php
// Carry list filters with each edit form so separate tabs remain independent.
function factoryProductListContext(array $input): array
{
    $search = is_string($input['return_search'] ?? null) ? trim($input['return_search']) : '';
    $status = $input['return_status'] ?? 'All';
    if (!in_array($status, ['All', 'Active', 'Inactive'], true)) $status = 'All';
    $profile = max(0, (int) ($input['return_profile'] ?? 0));
    return ['return_search' => $search, 'return_status' => $status, 'return_profile' => $profile];
}

function factoryProductListUrl(array $context): string
{
    return 'index.php?' . http_build_query([
        'search' => $context['return_search'], 'status' => $context['return_status'],
        'profile' => $context['return_profile'] ?? 0,
    ]);
}
