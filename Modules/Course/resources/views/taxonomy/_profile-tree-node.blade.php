@php
    $type = $node['node_type'] ?? 'node';
    $children = $node['children'] ?? [];
    $badgeClass = match ($type) {
        'education_level' => 'badge-dark',
        'awarding_body' => 'badge-success',
        'department' => 'badge-success',
        'programme' => 'badge-primary',
        'unit_group' => 'badge-info',
        'study_period' => 'badge-secondary',
        'unit' => 'badge-warning',
        default => 'badge-light',
    };
    $profileLabels = config("higher_education_taxonomy.profiles.{$profile}.labels", []);
    $typeLabel = $profileLabels[$type] ?? ucwords(str_replace('_', ' ', $type));
    $allowedChildren = config("higher_education_taxonomy.profiles.{$profile}.allowed_children.{$type}", []);
@endphp
<li class="rh-taxonomy-tree__item rh-taxonomy-tree__item--{{ $type }}">
    <div class="rh-taxonomy-tree__row">
        <span class="rh-taxonomy-tree__marker"></span>
        <span class="rh-taxonomy-tree__label">{{ $node['label'] }}</span>
        <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
        @if (($node['shared_count'] ?? 0) > 1)<span class="badge badge-success"><i class="fas fa-link"></i> {{ __('Shared :count', ['count' => $node['shared_count']]) }}</span>@endif

        @if (isset($node['assignment_id']))
            <a href="{{ route('admin.taxonomy.profiles.show', [$profile, 'edit_assignment' => $node['assignment_id']]) }}" class="btn btn-sm btn-outline-warning ml-auto" title="{{ __('Edit assignment') }}"><i class="fas fa-edit"></i></a>
            @if($node['active'] ?? true)
                <form method="POST" action="{{ route('admin.taxonomy.assignments.destroy', [$profile, $node['assignment_id']]) }}" class="taxonomy-unassign-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="{{ __('Deactivate assignment') }}"><i class="fas fa-unlink"></i></button></form>
            @else
                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                <form method="POST" action="{{ route('admin.taxonomy.assignments.restore', [$profile, $node['assignment_id']]) }}">@csrf<button class="btn btn-sm btn-outline-success" title="{{ __('Restore assignment') }}"><i class="fas fa-undo"></i></button></form>
            @endif
        @endif

        @if (is_int($node['id'] ?? null) && in_array($type, ['education_level', 'awarding_body', 'department', 'programme'], true))
            <a href="{{ route('admin.taxonomy.nodes.edit', [$node['id'], 'profile' => $profile]) }}" class="btn btn-sm btn-outline-warning {{ isset($node['assignment_id']) ? '' : 'ml-auto' }}" title="{{ __('Edit node') }}"><i class="fas fa-edit"></i></a>
            @if ($allowedChildren !== [])<a href="{{ route('admin.taxonomy.nodes.create', ['parent_id' => $node['id'], 'profile' => $profile]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Add :type', ['type' => strtolower($profileLabels[$allowedChildren[0]] ?? $allowedChildren[0])]) }}"><i class="fas fa-plus"></i></a>@endif
            @if($type !== 'education_level')<form method="POST" action="{{ route('admin.taxonomy.nodes.archive', $node['id']) }}" class="taxonomy-archive-node-form">@csrf @method('DELETE')<input type="hidden" name="return_profile" value="{{ $profile }}"><button class="btn btn-sm btn-outline-danger" title="{{ __('Archive branch') }}"><i class="fas fa-archive"></i></button></form>@endif
        @endif
    </div>
    @if ($children)<ul class="rh-taxonomy-tree__children">@foreach ($children as $child)@include('course::taxonomy._profile-tree-node', ['node' => $child, 'profile' => $profile])@endforeach</ul>@endif
</li>
