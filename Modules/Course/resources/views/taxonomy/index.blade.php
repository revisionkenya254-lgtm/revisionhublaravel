@extends('admin.master_layout')

@section('title')
    <title>{{ __('Taxonomy Management') }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header d-flex justify-content-between">
                <h1>{{ __('Taxonomy Management') }}</h1>
                <a href="{{ route('admin.taxonomy.nodes.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('Add Education Level') }}
                </a>
            </div>

            <div class="section-body">
                @if ($profileSummaries->isNotEmpty())
                    <div class="card mb-4 rh-higher-workspaces"><div class="card-header"><div><h4 class="mb-1">{{ __('Higher Education Workspaces') }}</h4><small class="text-muted">{{ __('Build institutions, departments, programmes, canonical units and assignments from the configured taxonomy architecture.') }}</small></div></div><div class="card-body"><div class="row">
                        @foreach($profileSummaries as $summary)
                            @php
                                $item = $summary['definition'];
                            @endphp
                            <div class="col-xl-4 col-md-6 mb-3"><div class="rh-workspace-card h-100" style="--profile-color:{{ $item['accent'] }}">
                                <div class="d-flex align-items-center mb-3"><span class="rh-workspace-card__icon"><i class="{{ $item['icon'] }}"></i></span><div><h5 class="mb-0">{{ $item['label'] }}</h5><small>{{ collect($item['hierarchy'] ?? [])->map(fn($type) => $item['labels'][$type] ?? ucwords(str_replace('_', ' ', $type)))->implode(' → ') }}</small></div></div>
                                <div class="rh-workspace-card__stats"><span><strong>{{ $summary['owners'] }}</strong> {{ $item['labels']['awarding_body'] ?? __('Owners') }}</span><span><strong>{{ $summary['departments'] }}</strong> {{ $item['labels']['department'] ?? __('Departments') }}</span><span><strong>{{ $summary['programmes'] }}</strong> {{ $item['labels']['programme'] ?? __('Programmes') }}</span><span><strong>{{ $summary['units'] }}</strong> {{ $item['labels']['unit'] ?? __('Units') }}</span><span><strong>{{ $summary['assignments'] }}</strong> {{ __('Assignments') }}</span></div>
                                <div class="d-flex flex-wrap mt-3" style="gap:.45rem"><a class="btn btn-sm btn-primary" href="{{ route('admin.taxonomy.profiles.show', $summary['profile']) }}"><i class="fas fa-sitemap"></i> {{ __('Manage :profile Curriculum', ['profile' => $item['label']]) }}</a><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.taxonomy.nodes.create', ['parent_id' => $summary['root']->id, 'profile' => $summary['profile']]) }}"><i class="fas fa-plus"></i> {{ __('Add :type', ['type' => $item['labels'][($item['allowed_children']['education_level'][0] ?? '')] ?? __('Structure')]) }}</a></div>
                            </div></div>
                        @endforeach
                    </div></div></div>
                @endif
                <div class="alert alert-info">
                    {{ __('School subjects and pathways are shared across every grade, form, and national assessment in an education level. Use the Shared Subjects action so all product builders and APIs remain uniform.') }}
                </div>

                @foreach ($levels as $level)
                    @continue(filled($level->taxonomy_profile))
                    @php
                        $classTypes = ['grade', 'form', 'national_exam'];
                        $classNodes = $level->subCategories->whereIn('node_type', $classTypes);
                        $levelLeaves = $sharedLeaves[$level->id] ?? collect();
                    @endphp
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h4 class="mb-1">{{ $level->translation?->name ?? $level->slug }}</h4>
                                <span class="badge badge-light">{{ $level->slug }}</span>
                                <span class="badge badge-primary">{{ str_replace('_', ' ', $level->node_type) }}</span>
                                <span class="badge badge-secondary">{{ __('Order') }}: {{ $level->order }}</span>
                            </div>
                            <div class="mt-2 mt-md-0">
                                <a href="{{ route('admin.taxonomy.nodes.edit', $level) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> {{ __('Edit Level') }}
                                </a>
                                <a href="{{ route('admin.taxonomy.nodes.create', ['parent_id' => $level->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> {{ __('Add Child') }}
                                </a>
                                @if ($classNodes->isNotEmpty())
                                    <a href="{{ route('admin.taxonomy.shared.create', $level) }}" class="btn btn-sm btn-success">
                                        <i class="fas fa-layer-group"></i> {{ __('Add Shared Subject') }}
                                    </a>
                                @endif
                                <form action="{{ route('admin.taxonomy.nodes.archive', $level) }}" method="POST" class="d-inline taxonomy-archive-form">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-archive"></i></button>
                                </form>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Child Node') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Order') }}</th>
                                            <th>{{ __('Children') }}</th>
                                            <th class="text-right">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($level->subCategories as $child)
                                            <tr>
                                                <td>
                                                    <strong>{{ $child->translation?->name ?? $child->slug }}</strong>
                                                    <div class="small text-muted">{{ $child->slug }}</div>
                                                </td>
                                                <td><span class="badge badge-info">{{ str_replace('_', ' ', $child->node_type) }}</span></td>
                                                <td>{{ $child->order }}</td>
                                                <td>{{ $child->subCategories->count() }}</td>
                                                <td class="text-right">
                                                    <a href="{{ route('admin.taxonomy.nodes.edit', $child) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                    @if (!in_array($child->node_type, $classTypes, true))
                                                        <a href="{{ route('admin.taxonomy.nodes.create', ['parent_id' => $child->id]) }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i></a>
                                                    @endif
                                                    <form action="{{ route('admin.taxonomy.nodes.archive', $child) }}" method="POST" class="d-inline taxonomy-archive-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-archive"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @if (!in_array($child->node_type, $classTypes, true))
                                                @foreach ($child->subCategories as $grandchild)
                                                    <tr class="bg-light">
                                                        <td class="pl-5">↳ {{ $grandchild->translation?->name ?? $grandchild->slug }} <span class="small text-muted">({{ $grandchild->slug }})</span></td>
                                                        <td><span class="badge badge-light">{{ str_replace('_', ' ', $grandchild->node_type) }}</span></td>
                                                        <td>{{ $grandchild->order }}</td>
                                                        <td>0</td>
                                                        <td class="text-right">
                                                            <a href="{{ route('admin.taxonomy.nodes.edit', $grandchild) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                            <form action="{{ route('admin.taxonomy.nodes.archive', $grandchild) }}" method="POST" class="d-inline taxonomy-archive-form">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-archive"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">{{ __('No child nodes yet.') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($classNodes->isNotEmpty())
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">{{ __('Shared Subjects / Pathways') }}</h5>
                                    <span class="text-muted small">{{ __('Applied to :count grade/exam nodes', ['count' => $classNodes->count()]) }}</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Order') }}</th><th>{{ __('Coverage') }}</th><th class="text-right">{{ __('Actions') }}</th></tr></thead>
                                        <tbody>
                                            @forelse ($levelLeaves as $leaf)
                                                <tr class="{{ $leaf['coverage'] !== $leaf['expected'] ? 'table-warning' : '' }}">
                                                    <td><strong>{{ $leaf['name'] }}</strong><div class="small text-muted">{{ $leaf['slug'] }}</div></td>
                                                    <td>{{ str_replace('_', ' ', $leaf['node_type']) }}</td>
                                                    <td>{{ $leaf['order'] }}</td>
                                                    <td>{{ $leaf['coverage'] }}/{{ $leaf['expected'] }}</td>
                                                    <td class="text-right">
                                                        <a href="{{ route('admin.taxonomy.shared.edit', [$level, $leaf['slug']]) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                        <form action="{{ route('admin.taxonomy.shared.archive', [$level, $leaf['slug']]) }}" method="POST" class="d-inline taxonomy-archive-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-archive"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center text-muted">{{ __('No shared subjects configured.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>{{ __('Archived Taxonomy Nodes') }}</h4>
                        @if ($archivedNodes->isNotEmpty())
                            <form id="bulk-purge-taxonomy-form" action="{{ route('admin.taxonomy.nodes.bulk-destroy') }}" method="POST" class="taxonomy-purge-form">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">
                                    <i class="fas fa-trash-alt"></i> {{ __('Delete selected permanently') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th style="width:36px"><input type="checkbox" class="taxonomy-select-all" aria-label="{{ __('Select all archived nodes') }}"></th><th>{{ __('Name') }}</th><th>{{ __('Slug') }}</th><th>{{ __('Type') }}</th><th>{{ __('Archived') }}</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($archivedNodes as $archived)
                                    <tr>
                                        <td><input type="checkbox" name="nodes[]" value="{{ $archived->id }}" form="bulk-purge-taxonomy-form" class="taxonomy-node-checkbox" aria-label="{{ __('Select :name', ['name' => $archived->translation?->name ?? $archived->slug]) }}"></td>
                                        <td>{{ $archived->translation?->name ?? $archived->slug }}</td>
                                        <td>{{ $archived->slug }}</td>
                                        <td>{{ str_replace('_', ' ', $archived->node_type) }}</td>
                                        <td>{{ $archived->deleted_at?->diffForHumans() }}</td>
                                        <td class="text-right text-nowrap">
                                            <form action="{{ route('admin.taxonomy.nodes.restore', $archived->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-success" type="submit"><i class="fas fa-undo"></i> {{ __('Restore') }}</button>
                                            </form>
                                            <form action="{{ route('admin.taxonomy.nodes.destroy', $archived->id) }}" method="POST" class="d-inline taxonomy-purge-form">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash-alt"></i> {{ __('Delete permanently') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">{{ __('No archived taxonomy nodes.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h4>{{ __('Recent Taxonomy Changes') }}</h4></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>{{ __('When') }}</th><th>{{ __('Action') }}</th><th>{{ __('Administrator') }}</th><th>{{ __('Category ID') }}</th><th>{{ __('Batch') }}</th></tr></thead>
                            <tbody>
                                @forelse ($recentChanges as $change)
                                    <tr>
                                        <td>{{ $change->created_at?->diffForHumans() }}</td>
                                        <td><span class="badge badge-info">{{ str_replace('_', ' ', $change->action) }}</span></td>
                                        <td>{{ $change->admin?->name ?? __('System') }}</td>
                                        <td>{{ $change->category_id ?? '-' }}</td>
                                        <td><code>{{ substr($change->batch_uuid, 0, 8) }}</code></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ __('No audited taxonomy changes yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('css')
<style>
.rh-higher-workspaces{border-top:4px solid #4f46e5}.rh-workspace-card{background:#fff;border:1px solid #e5e7eb;border-top:4px solid var(--profile-color);border-radius:12px;padding:1rem;box-shadow:0 4px 12px rgba(15,23,42,.04)}.rh-workspace-card__icon{align-items:center;background:color-mix(in srgb,var(--profile-color) 12%,white);border-radius:10px;color:var(--profile-color);display:inline-flex;font-size:1.1rem;height:42px;justify-content:center;margin-right:.75rem;width:42px}.rh-workspace-card__stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}.rh-workspace-card__stats span{background:#f8fafc;border-radius:7px;color:#64748b;padding:.45rem .55rem}.rh-workspace-card__stats strong{color:#0f172a}
</style>
@endpush

@push('js')
    <script>
        const taxonomyScrollKey = 'admin-taxonomy-scroll-position';
        const taxonomyPrefix = window.location.pathname.replace(/\/$/, '') + '/';
        const savedScrollPosition = window.sessionStorage.getItem(taxonomyScrollKey);

        if (savedScrollPosition !== null) {
            window.sessionStorage.removeItem(taxonomyScrollKey);
            window.requestAnimationFrame(function () {
                window.scrollTo(0, Number(savedScrollPosition));
            });
        }

        function saveTaxonomyScrollPosition() {
            window.sessionStorage.setItem(taxonomyScrollKey, String(window.scrollY));
        }

        document.addEventListener('click', function (event) {
            const link = event.target.closest('a[href]');
            if (link && new URL(link.href, window.location.href).pathname.startsWith(taxonomyPrefix)) {
                saveTaxonomyScrollPosition();
            }
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.classList.contains('taxonomy-purge-form')) {
                    if (form.id === 'bulk-purge-taxonomy-form'
                        && document.querySelectorAll('.taxonomy-node-checkbox:checked').length === 0) {
                        event.preventDefault();
                        window.alert(@json(__('Select at least one archived taxonomy node.')));
                        return;
                    }

                    if (!window.confirm(@json(__('Permanently delete the selected archived taxonomy data? This cannot be undone.')))) {
                        event.preventDefault();
                        return;
                    }
                }

                if (form.classList.contains('taxonomy-archive-form')
                    && !window.confirm(@json(__('Archive this taxonomy branch? This is blocked when products or courses still use it.')))) {
                    event.preventDefault();
                    return;
                }

                saveTaxonomyScrollPosition();
            });
        });

        document.querySelectorAll('.taxonomy-select-all').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                document.querySelectorAll('.taxonomy-node-checkbox').forEach(function (nodeCheckbox) {
                    nodeCheckbox.checked = checkbox.checked;
                });
            });
        });
    </script>
@endpush
