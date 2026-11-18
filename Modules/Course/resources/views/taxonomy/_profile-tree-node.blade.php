@php
    $type = $node['node_type'] ?? 'node';
    $children = $node['children'] ?? [];
    $badgeClass = match ($type) {
        'education_level' => 'badge-dark',
        'department' => 'badge-success',
        'programme' => 'badge-primary',
        'unit_group' => 'badge-info',
        'unit' => 'badge-warning',
        default => 'badge-light',
    };
@endphp
<li class="rh-taxonomy-tree__item rh-taxonomy-tree__item--{{ $type }}">
    <div class="rh-taxonomy-tree__row">
        <span class="rh-taxonomy-tree__marker"></span>
        <span class="rh-taxonomy-tree__label">{{ $node['label'] }}</span>
        <span class="badge {{ $badgeClass }}">{{ str_replace('_', ' ', $type) }}</span>
        @if (($node['shared_count'] ?? 0) > 1)
            <span class="badge badge-success"><i class="fas fa-link"></i> {{ __('Shared :count', ['count' => $node['shared_count']]) }}</span>
        @endif
        @if (isset($node['assignment_id']))
            <form method="POST" action="{{ route('admin.taxonomy.assignments.destroy', [$profile, $node['assignment_id']]) }}" class="ml-auto taxonomy-unassign-form">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" title="{{ __('Remove assignment') }}"><i class="fas fa-unlink"></i></button>
            </form>
        @endif
        @if (is_int($node['id'] ?? null) && in_array($type, ['education_level', 'awarding_body', 'department', 'programme'], true))
            <a href="{{ route('admin.taxonomy.nodes.edit', $node['id']) }}" class="btn btn-sm btn-outline-warning ml-auto" title="{{ __('Edit node') }}"><i class="fas fa-edit"></i></a>
            @if (in_array($type, ['education_level', 'awarding_body', 'department'], true))
                <a href="{{ route('admin.taxonomy.nodes.create', ['parent_id' => $node['id']]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Add child node') }}"><i class="fas fa-plus"></i></a>
            @endif
        @endif
    </div>
    @if ($children)
        <ul class="rh-taxonomy-tree__children">
            @foreach ($children as $child)
                @include('course::taxonomy._profile-tree-node', ['node' => $child, 'profile' => $profile])
            @endforeach
        </ul>
    @endif
</li>
