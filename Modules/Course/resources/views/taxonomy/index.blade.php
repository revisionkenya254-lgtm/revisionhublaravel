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
                <div class="alert alert-info">
                    {{ __('School subjects and pathways are shared across every grade, form, and national assessment in an education level. Use the Shared Subjects action so all product builders and APIs remain uniform.') }}
                </div>

                @foreach ($levels as $level)
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
                    <div class="card-header"><h4>{{ __('Archived Taxonomy Nodes') }}</h4></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Slug') }}</th><th>{{ __('Type') }}</th><th>{{ __('Archived') }}</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($archivedNodes as $archived)
                                    <tr>
                                        <td>{{ $archived->translation?->name ?? $archived->slug }}</td>
                                        <td>{{ $archived->slug }}</td>
                                        <td>{{ str_replace('_', ' ', $archived->node_type) }}</td>
                                        <td>{{ $archived->deleted_at?->diffForHumans() }}</td>
                                        <td class="text-right">
                                            <form action="{{ route('admin.taxonomy.nodes.restore', $archived->id) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-sm btn-success" type="submit"><i class="fas fa-undo"></i> {{ __('Restore') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ __('No archived taxonomy nodes.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script>
        document.querySelectorAll('.taxonomy-archive-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(@json(__('Archive this taxonomy branch? This is blocked when products or courses still use it.')))) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
