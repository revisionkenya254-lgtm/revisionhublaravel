@extends('admin.master_layout')

@php
    $usesProgrammeDepartments = in_array('department', $profileConfig['creation_fields'] ?? [], true);
@endphp

@section('title')<title>{{ $profileConfig['label'] }} {{ __('Taxonomy') }}</title>@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header d-flex justify-content-between">
            <div><h1>{{ $profileConfig['label'] }} {{ __('Curriculum') }}</h1><small>{{ __('Taxonomy-driven programme structures and reusable canonical units') }}</small></div>
            <a href="{{ route('admin.taxonomy.index') }}" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> {{ __('All taxonomy') }}</a>
        </div>
        <div class="section-body">
            <div class="rh-profile-tabs mb-4">
                @foreach (config('higher_education_taxonomy.profiles', []) as $profileSlug => $item)
                    @php
                        $available = \Modules\Course\app\Models\CourseCategory::whereNull('parent_id')
                            ->where('taxonomy_profile', $profileSlug)
                            ->exists();
                    @endphp
                    @if ($available)<a class="rh-profile-tab {{ $profileSlug === $profile ? 'is-active' : '' }}" style="--profile-color: {{ $item['accent'] }}" href="{{ route('admin.taxonomy.profiles.show', $profileSlug) }}"><i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}</a>@endif
                @endforeach
            </div>
            <div class="alert alert-light border mb-4">
                <strong>{{ __('Profile architecture:') }}</strong> {{ $profileConfig['label'] }}
                @foreach(($profileConfig['hierarchy'] ?? ['awarding_body', 'department', 'programme']) as $nodeType)<i class="fas fa-angle-right mx-1"></i> {{ $profileConfig['labels'][$nodeType] ?? ucwords(str_replace('_', ' ', $nodeType)) }}@endforeach
                @foreach(($profileConfig['period_fields'] ?? []) as $periodField)<i class="fas fa-angle-right mx-1"></i> {{ ucwords(str_replace('_', ' ', $periodField)) }}@endforeach
                <i class="fas fa-angle-right mx-1"></i> {{ __('Unit Group') }} <i class="fas fa-angle-right mx-1"></i> {{ $profileConfig['labels']['unit'] ?? __('Unit') }} <i class="fas fa-angle-right mx-1"></i> {{ __('Resources') }}
            </div>

            <div class="row">
                <div class="col-xl-9"><div class="card rh-profile-card" style="--profile-color: {{ $profileConfig['accent'] }}"><div class="card-header d-flex justify-content-between align-items-center flex-wrap"><div><h4 class="mb-0"><i class="fas fa-sitemap"></i> {{ __('Programme structure') }}</h4><small class="text-muted">{{ __('Assign or edit units directly beside the relevant programme, period, or unit group.') }}</small></div><div class="d-flex flex-wrap mt-2 mt-md-0" style="gap:.4rem"><button type="button" id="expand-all-programmes" class="btn btn-sm btn-outline-secondary"><i class="fas fa-angle-double-down"></i> {{ __('Expand all') }}</button><button type="button" id="collapse-all-programmes" class="btn btn-sm btn-outline-secondary"><i class="fas fa-angle-double-up"></i> {{ __('Collapse all') }}</button><button type="button" class="btn btn-success js-open-assignment-modal" data-mode="create" data-programme-id="{{ $programmes->first()?->id }}" data-defaults="{}" @disabled($programmes->isEmpty() || $units->where('status', true)->isEmpty())><i class="fas fa-link"></i> {{ __('Assign unit') }}</button></div></div><div class="card-body"><ul class="rh-taxonomy-tree">@include('course::taxonomy._profile-tree-node', ['node' => $tree, 'profile' => $profile])</ul></div></div></div>
                <div class="col-xl-3">
                    <div class="card"><div class="card-header"><h4>{{ __('Create and assign unit') }}</h4></div><div class="card-body">
                        <p class="text-muted">{{ __('Choose the curriculum path first, then create the unit directly inside that programme and unit group.') }}</p>
                        <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#unit-creation-modal" @disabled($programmes->isEmpty())><i class="fas fa-plus"></i> {{ __('Create and assign unit') }}</button>
                        @if($programmes->isEmpty())<small class="text-danger d-block mt-2">{{ __('Create a programme before creating its units.') }}</small>@endif
                    </div></div>
                    {{-- Assignment creation and editing now live in the contextual modal below.
                    <div class="card"><div class="card-header d-flex justify-content-between"><h4>{{ $editingAssignment ? __('Edit programme unit') : __('Assign existing unit') }}</h4>@if($editingAssignment)<a href="{{ route('admin.taxonomy.profiles.show', $profile) }}" class="btn btn-sm btn-outline-secondary">{{ __('Cancel') }}</a>@endif</div><div class="card-body">
                        @if ($programmes->isEmpty())<p class="text-muted">{{ __('Create the required programme or qualification structure first.') }}</p>@else
                            <form method="POST" id="unit-assignment-form" action="{{ $editingAssignment ? route('admin.taxonomy.assignments.update', [$profile, $editingAssignment]) : '' }}" data-action-template="{{ route('admin.taxonomy.assignments.store', [$profile, '__PROGRAMME__']) }}" data-editing="{{ $editingAssignment ? '1' : '0' }}">@csrf
                                @if($editingAssignment) @method('PUT') @endif
                                <div class="form-group"><label>{{ $profileConfig['labels']['programme'] ?? __('Programme') }} *</label><select id="assignment-programme" name="programme_node_id" class="form-control" required>@foreach($programmes as $programme)<option value="{{ $programme->id }}" data-owner-id="{{ $programmeOwnerIds[$programme->id] ?? $root->id }}" @selected((int)old('programme_node_id', $editingAssignment?->programme_node_id) === $programme->id)>{{ $programme->name }}</option>@endforeach</select></div>
                                <div class="form-group"><label>{{ $profileConfig['labels']['unit'] ?? __('Canonical unit') }} *</label><select id="assignment-unit" name="curriculum_unit_id" class="form-control" required>@foreach($units->filter(fn($item) => $item->status || $editingAssignment?->curriculum_unit_id === $item->id) as $unit)<option value="{{ $unit->id }}" data-owner-id="{{ $unit->awarding_body_id }}" @selected((int)old('curriculum_unit_id', $editingAssignment?->curriculum_unit_id) === $unit->id)>{{ $unit->name }} · {{ $unit->awardingBody?->name }} ({{ __('used by :count', ['count' => $unit->assignments_count]) }})</option>@endforeach</select></div>
                                <div class="form-group"><label>{{ __('Unit group') }} *</label><select name="group_type" class="form-control">@foreach(($profileConfig['group_types'] ?? []) as $groupValue => $groupLabel)<option value="{{ $groupValue }}" @selected(old('group_type', $editingAssignment?->group_type) === $groupValue)>{{ $groupLabel }}</option>@endforeach</select></div>
                                @foreach(($profileConfig['period_fields'] ?? []) as $periodField)<div class="form-group"><label>{{ ucwords(str_replace('_', ' ', $periodField)) }}</label><input name="{{ $periodField }}" class="form-control" value="{{ old($periodField, $editingAssignment?->{$periodField}) }}" placeholder="{{ match($periodField) { 'study_year' => __('e.g. Year 1'), 'semester' => __('e.g. Semester 1'), 'module' => __('e.g. Module 1'), 'stage' => __('e.g. Intermediate Level'), 'section' => __('e.g. Section 2'), default => '' } }}"></div>@endforeach
                                <div class="row"><div class="form-group col-6"><label>{{ __('Display order') }}</label><input type="number" min="0" max="65535" name="display_order" class="form-control" value="{{ old('display_order', $editingAssignment?->display_order ?? 0) }}"></div><div class="form-group col-6"><label>{{ __('Credit hours') }}</label><input type="number" min="0" max="999" step="0.01" name="credit_hours" class="form-control" value="{{ old('credit_hours', $editingAssignment?->credit_hours) }}"></div></div>
                                <input type="hidden" name="is_mandatory" value="0"><div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="assignment-mandatory" name="is_mandatory" value="1" @checked((bool)old('is_mandatory', $editingAssignment?->is_mandatory ?? true))><label class="form-check-label" for="assignment-mandatory">{{ __('Mandatory unit') }}</label></div>
                                <button class="btn btn-primary btn-block" @disabled($units->where('status', true)->isEmpty())><i class="fas fa-link"></i> {{ $editingAssignment ? __('Update assignment') : __('Assign unit') }}</button>
                            </form>
                        @endif
                    </div></div>
                    --}}
                </div>
            </div>

            <div class="modal fade" id="unit-creation-modal" tabindex="-1" role="dialog" aria-labelledby="unit-creation-modal-title" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
                    <form method="POST" action="{{ route('admin.taxonomy.units.store', $profile) }}" id="unit-creation-form">@csrf
                        <input type="hidden" name="_create_and_assign" value="1">
                        <input type="hidden" name="display_order" value="0">
                        <input type="hidden" name="is_mandatory" value="1">
                        <div class="modal-header"><div><h5 class="modal-title" id="unit-creation-modal-title">{{ __('Create and assign :unit', ['unit' => $profileConfig['labels']['unit'] ?? __('Unit')]) }}</h5><small class="text-muted">{{ __('The unit will be created once and immediately assigned to the selected curriculum path.') }}</small></div><button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}"><span aria-hidden="true">&times;</span></button></div>
                        <div class="modal-body">
                            @if(old('_create_and_assign') && $errors->any())<div class="alert alert-danger"><strong>{{ __('Please correct the following:') }}</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
                            <h6 class="text-primary mb-3"><i class="fas fa-sitemap"></i> {{ __('Curriculum path') }}</h6>
                            <div class="row">
                                @if($usesProgrammeDepartments)
                                    <div class="form-group col-md-6"><label>{{ $profileConfig['labels']['department'] ?? __('Department') }} *</label><select id="unit-create-department" name="department_id" class="form-control" required><option value="">{{ __('Select :department', ['department' => $profileConfig['labels']['department'] ?? __('Department')]) }}</option>@foreach($departments as $department)<option value="{{ $department['id'] }}" @selected((string)old('department_id') === (string)$department['id'])>{{ !($profileConfig['root_is_owner'] ?? false) && filled($department['owner_name']) ? $department['owner_name'].' / ' : '' }}{{ $department['name'] }}</option>@endforeach</select></div>
                                @endif
                                <div class="form-group col-md-6"><label>{{ $profileConfig['labels']['programme'] ?? __('Programme') }} *</label><select id="unit-create-programme" name="programme_node_id" class="form-control" required><option value="">{{ __('Select :programme', ['programme' => $profileConfig['labels']['programme'] ?? __('Programme')]) }}</option>@foreach($programmes as $programme)<option value="{{ $programme->id }}" data-department-id="{{ $programmeDepartments[$programme->id]['id'] ?? '' }}" data-owner-name="{{ $programmeDepartments[$programme->id]['owner_name'] ?? '' }}" @selected((string)old('programme_node_id') === (string)$programme->id)>{{ !($profileConfig['root_is_owner'] ?? false) && filled($programmeDepartments[$programme->id]['owner_name'] ?? null) ? ($programmeDepartments[$programme->id]['owner_name'].' / ') : '' }}{{ $programme->name }}</option>@endforeach</select></div>
                                @foreach(($profileConfig['period_fields'] ?? []) as $periodField)
                                    <div class="form-group col-md-6"><label>{{ ucwords(str_replace('_', ' ', $periodField)) }} *</label><select name="{{ $periodField }}" class="form-control" required><option value="">{{ __('Select :period', ['period' => ucwords(str_replace('_', ' ', $periodField))]) }}</option>@foreach(($periodOptions[$periodField] ?? []) as $periodOption)<option value="{{ $periodOption }}" @selected(old($periodField) === $periodOption)>{{ $periodOption }}</option>@endforeach</select></div>
                                @endforeach
                                <div class="form-group col-md-6"><label>{{ __('Unit group') }} *</label><select name="group_type" class="form-control" required><option value="">{{ __('Select unit group') }}</option>@foreach(($profileConfig['group_types'] ?? []) as $groupValue => $groupLabel)<option value="{{ $groupValue }}" @selected(old('group_type') === $groupValue)>{{ $groupLabel }}</option>@endforeach</select></div>
                            </div>
                            <hr>
                            <h6 class="text-primary mb-3"><i class="fas fa-book"></i> {{ $profileConfig['labels']['unit'] ?? __('Unit') }} {{ __('details') }}</h6>
                            <div class="row">
                                <div class="form-group col-md-8"><label>{{ $profileConfig['labels']['unit'] ?? __('Unit') }} {{ __('name') }} *</label><input name="name" class="form-control" required value="{{ old('name') }}" placeholder="{{ __('Enter the official unit name') }}"></div>
                                <div class="form-group col-md-4"><label>{{ __('Official code') }}</label><input name="code" class="form-control" value="{{ old('code') }}"></div>
                                <div class="form-group col-md-4"><label>{{ __('Curriculum version') }} *</label><input name="curriculum_version" class="form-control" required value="{{ old('curriculum_version', 'current') }}"></div>
                                <div class="form-group col-md-8"><label>{{ __('Description') }}</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> {{ __('Create and assign unit') }}</button></div>
                    </form>
                </div></div>
            </div>

            <div class="modal fade" id="assignment-modal" tabindex="-1" role="dialog" aria-labelledby="assignment-modal-title" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
                    <form method="POST" id="unit-assignment-form"
                        data-create-action="{{ route('admin.taxonomy.assignments.store', [$profile, '__PROGRAMME__']) }}"
                        data-update-action="{{ route('admin.taxonomy.assignments.update', [$profile, '__ASSIGNMENT__']) }}">
                        @csrf
                        <input type="hidden" name="_method" id="assignment-method" value="PUT" disabled>
                        <div class="modal-header"><div><h5 class="modal-title" id="assignment-modal-title">{{ __('Assign unit') }}</h5><small id="assignment-modal-context" class="text-muted"></small></div><button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}"><span aria-hidden="true">&times;</span></button></div>
                        <div class="modal-body">
                            <div id="assignment-form-errors" class="alert alert-danger d-none"></div>
                            <div class="row">
                                @if($usesProgrammeDepartments)<div class="form-group col-md-6"><label>{{ $profileConfig['labels']['department'] ?? __('Department') }} *</label><select id="assignment-department" class="form-control" required><option value="">{{ __('Select :department', ['department' => $profileConfig['labels']['department'] ?? __('Department')]) }}</option>@foreach($departments as $department)<option value="{{ $department['id'] }}">{{ $department['name'] }}</option>@endforeach</select></div>@endif
                                <div class="form-group col-md-6"><label>{{ $profileConfig['labels']['programme'] ?? __('Programme') }} *</label><select id="assignment-programme" name="programme_node_id" class="form-control" required>@foreach($programmes as $programme)<option value="{{ $programme->id }}" data-owner-id="{{ $programmeOwnerIds[$programme->id] ?? $root->id }}" data-department-id="{{ $programmeDepartments[$programme->id]['id'] ?? '' }}" data-department-name="{{ $programmeDepartments[$programme->id]['name'] ?? '' }}">{{ $programme->name }}</option>@endforeach</select></div>
                                <div class="form-group col-md-6"><label>{{ $profileConfig['labels']['unit'] ?? __('Canonical unit') }} *</label><select id="assignment-unit" name="curriculum_unit_id" class="form-control" required>@foreach($units as $unit)<option value="{{ $unit->id }}" data-owner-id="{{ $unit->awarding_body_id }}" data-active="{{ $unit->status ? '1' : '0' }}">{{ $unit->name }} &middot; {{ $unit->awardingBody?->name }} ({{ __('used by :count', ['count' => $unit->assignments_count]) }})</option>@endforeach</select></div>
                                <div class="form-group col-md-6"><label>{{ __('Unit group') }} *</label><select id="assignment-group" name="group_type" class="form-control" required>@foreach(($profileConfig['group_types'] ?? []) as $groupValue => $groupLabel)<option value="{{ $groupValue }}">{{ $groupLabel }}</option>@endforeach</select></div>
                                @foreach(($profileConfig['period_fields'] ?? []) as $periodField)<div class="form-group col-md-6"><label>{{ ucwords(str_replace('_', ' ', $periodField)) }}</label><input name="{{ $periodField }}" class="form-control assignment-period-field" value="" placeholder="{{ match($periodField) { 'study_year' => __('e.g. Year 1'), 'semester' => __('e.g. Semester 1'), 'module' => __('e.g. Module 1'), 'stage' => __('e.g. Intermediate Level'), 'section' => __('e.g. Section 2'), default => '' } }}"></div>@endforeach
                                <div class="form-group col-md-3"><label>{{ __('Display order') }}</label><input type="number" min="0" max="65535" name="display_order" class="form-control" value="0" required></div>
                                <div class="form-group col-md-3"><label>{{ __('Credit hours') }}</label><input type="number" min="0" max="999" step="0.01" name="credit_hours" class="form-control"></div>
                            </div>
                            <input type="hidden" name="is_mandatory" value="0"><div class="form-check"><input type="checkbox" class="form-check-input" id="assignment-mandatory" name="is_mandatory" value="1" checked><label class="form-check-label" for="assignment-mandatory">{{ __('Mandatory unit') }}</label></div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" id="assignment-submit" class="btn btn-primary" @disabled($units->where('status', true)->isEmpty())><i class="fas fa-link"></i> <span>{{ __('Assign unit') }}</span></button></div>
                    </form>
                </div></div>
            </div>

            <div class="modal fade" id="assignment-context-modal" tabindex="-1" role="dialog" aria-labelledby="assignment-context-title" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
                    <div class="modal-header"><div><h5 class="modal-title" id="assignment-context-title">{{ __('Manage assigned units') }}</h5><small id="assignment-context-description" class="text-muted"></small></div><button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}"><span aria-hidden="true">&times;</span></button></div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span id="assignment-context-type" class="badge badge-info"></span> <span id="assignment-context-count" class="text-muted"></span></div><button type="button" id="assignment-context-add" class="btn btn-success"><i class="fas fa-plus"></i> {{ __('Add unit here') }}</button></div>
                        <div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>{{ __('Unit') }}</th><th>{{ __('Code') }}</th><th>{{ __('Unit group') }}</th><th>{{ __('Study period') }}</th><th>{{ __('Status') }}</th><th class="text-right">{{ __('Action') }}</th></tr></thead><tbody id="assignment-context-rows"></tbody></table></div>
                        <div id="assignment-context-empty" class="text-center text-muted py-4 d-none">{{ __('No units are assigned in this context yet. Use Add unit here to begin.') }}</div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">{{ __('Close') }}</button></div>
                </div></div>
            </div>

            <div class="card mt-4"><div class="card-header"><h4><i class="fas fa-book"></i> {{ __('Canonical Unit Library') }}</h4></div><div class="card-body table-responsive"><table class="table table-sm table-bordered">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Owner') }}</th><th>{{ __('Code') }}</th><th>{{ __('Version') }}</th><th>{{ __('Usage') }}</th><th>{{ __('Actions') }}</th></tr></thead>
                <tbody>@forelse($units as $unit)<tr class="{{ $unit->status ? '' : 'table-secondary' }}">
                    <td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="name" value="{{ $unit->name }}" required><input form="unit-form-{{ $unit->id }}" type="hidden" name="slug" value="{{ $unit->slug }}"></td><td>{{ $unit->awardingBody?->name }}</td><td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="code" value="{{ $unit->code }}"></td><td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="curriculum_version" value="{{ $unit->curriculum_version }}" required></td><td><span class="badge badge-success"><i class="fas fa-link"></i> {{ trans_choice(':count programme|:count programmes', $unit->assignments_count, ['count' => $unit->assignments_count]) }}</span></td>
                    <td class="text-nowrap"><form id="unit-form-{{ $unit->id }}" method="POST" action="{{ route('admin.taxonomy.units.update', [$profile, $unit]) }}" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-warning" @disabled(!$unit->status)><i class="fas fa-save"></i> {{ __('Update') }}</button></form> @if($unit->status)<form method="POST" action="{{ route('admin.taxonomy.units.archive', [$profile, $unit]) }}" class="taxonomy-archive-unit-form d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-archive"></i> {{ __('Archive') }}</button></form>@else<form method="POST" action="{{ route('admin.taxonomy.units.restore', [$profile, $unit]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success"><i class="fas fa-undo"></i> {{ __('Restore') }}</button></form> <form method="POST" action="{{ route('admin.taxonomy.units.destroy', [$profile, $unit]) }}" class="taxonomy-purge-unit-form d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> {{ __('Delete permanently') }}</button></form>@endif</td>
                </tr>@empty<tr><td colspan="6" class="text-center text-muted">{{ __('No canonical units yet.') }}</td></tr>@endforelse</tbody>
            </table></div></div>
            @if($archivedNodes->isNotEmpty())
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-archive"></i> {{ __('Archived profile nodes') }}</h4>
                        <form id="bulk-purge-profile-form" method="POST" action="{{ route('admin.taxonomy.nodes.bulk-destroy') }}" class="taxonomy-purge-form">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_profile" value="{{ $profile }}">
                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> {{ __('Delete selected permanently') }}</button>
                        </form>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th style="width:36px"><input type="checkbox" id="profile-archived-select-all" aria-label="{{ __('Select all archived nodes') }}"></th><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Parent') }}</th><th></th></tr></thead>
                            <tbody>
                                @foreach($archivedNodes as $archived)
                                    <tr>
                                        <td><input type="checkbox" name="nodes[]" value="{{ $archived->id }}" form="bulk-purge-profile-form" class="profile-archived-node-checkbox" aria-label="{{ __('Select :name', ['name' => $archived->name]) }}"></td>
                                        <td>{{ $archived->name }}</td>
                                        <td>{{ $profileConfig['labels'][$archived->node_type] ?? ucwords(str_replace('_', ' ', $archived->node_type)) }}</td>
                                        <td>{{ $archived->parentCategory?->name }}</td>
                                        <td class="text-nowrap">
                                            <form method="POST" action="{{ route('admin.taxonomy.nodes.restore', $archived->id) }}" class="d-inline">@csrf<input type="hidden" name="return_profile" value="{{ $profile }}"><button class="btn btn-sm btn-outline-success"><i class="fas fa-undo"></i> {{ __('Restore') }}</button></form>
                                            <form method="POST" action="{{ route('admin.taxonomy.nodes.destroy', $archived->id) }}" class="d-inline taxonomy-purge-form">@csrf @method('DELETE')<input type="hidden" name="return_profile" value="{{ $profile }}"><button class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> {{ __('Delete permanently') }}</button></form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('css')
<style>.rh-profile-tabs{display:flex;gap:.65rem;flex-wrap:wrap}.rh-profile-tab{background:#fff;border:1px solid #e5e7eb;border-radius:10px;color:#374151;padding:.7rem 1rem}.rh-profile-tab.is-active{border-color:var(--profile-color);box-shadow:0 0 0 2px color-mix(in srgb,var(--profile-color) 18%,transparent);color:var(--profile-color);font-weight:700}.rh-profile-card{border-top:4px solid var(--profile-color)}.rh-taxonomy-tree,.rh-taxonomy-tree__children{list-style:none;margin:0;padding-left:0}.rh-taxonomy-tree__children{border-left:2px solid #e5e7eb;margin-left:.8rem;padding-left:1.4rem}.rh-taxonomy-tree__row{align-items:center;border-radius:7px;display:flex;gap:.55rem;min-height:42px;padding:.2rem .35rem;position:relative}.rh-taxonomy-tree__row:hover{background:#f8fafc}.rh-taxonomy-tree__row .btn{flex:0 0 auto}.rh-taxonomy-tree__marker{background:#cbd5e1;border-radius:50%;height:8px;width:8px}.rh-taxonomy-tree__label{font-weight:600}.rh-taxonomy-tree__item--unit .rh-taxonomy-tree__label{font-weight:500}.rh-taxonomy-tree__item--resource .rh-taxonomy-tree__label{font-weight:400;color:#64748b}.rh-programme-toggle{color:#64748b!important;width:18px}.rh-programme-toggle i{transition:transform .18s ease}.rh-programme-toggle[aria-expanded="true"] i{transform:rotate(90deg)}#unit-creation-modal,#assignment-modal,#assignment-context-modal{z-index:2000!important}body>.modal-backdrop{z-index:1990!important}#unit-creation-modal .modal-body,#assignment-modal .modal-body,#assignment-context-modal .modal-body{max-height:68vh;overflow-y:auto}@media(max-width:767px){.rh-taxonomy-tree__row{flex-wrap:wrap}.rh-taxonomy-tree__children{margin-left:.25rem;padding-left:.75rem}}</style>
@endpush

@push('js')
@php
    $editingAssignmentPayload = $editingAssignment
        ? $editingAssignment->only([
            'id', 'programme_node_id', 'curriculum_unit_id', 'group_type', 'study_year', 'semester',
            'module', 'stage', 'section', 'credit_hours', 'is_mandatory', 'display_order',
        ])
        : null;
@endphp
<script>
const unitCreationModal=document.getElementById('unit-creation-modal');
if(unitCreationModal){
    if(unitCreationModal.parentElement!==document.body)document.body.appendChild(unitCreationModal);
    const creationDepartment=document.getElementById('unit-create-department');
    const creationProgramme=document.getElementById('unit-create-programme');
    const filterCreationProgrammes=()=>{
        const departmentId=creationDepartment?.value||'';
        Array.from(creationProgramme.options).forEach(option=>{
            if(!option.value){option.hidden=false;option.disabled=false;return;}
            const visible=!creationDepartment||Boolean(departmentId)&&String(option.dataset.departmentId||'')===String(departmentId);
            option.hidden=!visible;
            option.disabled=!visible;
        });
        if(creationProgramme.selectedOptions[0]?.disabled)creationProgramme.value='';
    };
    creationDepartment?.addEventListener('change',()=>{creationProgramme.value='';filterCreationProgrammes();});
    filterCreationProgrammes();
    @if(old('_create_and_assign') && $errors->any()) window.jQuery(unitCreationModal).modal('show'); @endif
}
const programmeTreeStateKey=@json('taxonomy-programme-tree-'.$profile);
let programmeTreeState={};
try{programmeTreeState=JSON.parse(localStorage.getItem(programmeTreeStateKey)||'{}');}catch(error){programmeTreeState={};}
const programmeToggles=Array.from(document.querySelectorAll('.js-programme-toggle'));
const setProgrammeExpanded=(toggle,expanded,persist=true)=>{
    const children=document.getElementById(toggle.dataset.target);
    if(!children)return;
    toggle.setAttribute('aria-expanded',expanded?'true':'false');
    children.hidden=!expanded;
    if(persist){programmeTreeState[toggle.dataset.programmeId]=expanded;try{localStorage.setItem(programmeTreeStateKey,JSON.stringify(programmeTreeState));}catch(error){}}
};
programmeToggles.forEach(toggle=>{
    setProgrammeExpanded(toggle,programmeTreeState[toggle.dataset.programmeId]===true,false);
    toggle.addEventListener('click',()=>setProgrammeExpanded(toggle,toggle.getAttribute('aria-expanded')!=='true'));
});
document.getElementById('expand-all-programmes')?.addEventListener('click',()=>programmeToggles.forEach(toggle=>setProgrammeExpanded(toggle,true)));
document.getElementById('collapse-all-programmes')?.addEventListener('click',()=>programmeToggles.forEach(toggle=>setProgrammeExpanded(toggle,false)));
const assignmentForm=document.getElementById('unit-assignment-form');
const assignmentScrollKey='taxonomy-assignment-scroll';
const storedAssignmentScroll=sessionStorage.getItem(assignmentScrollKey);
if(storedAssignmentScroll!==null){sessionStorage.removeItem(assignmentScrollKey);requestAnimationFrame(()=>window.scrollTo(0,Number(storedAssignmentScroll)));}
if(assignmentForm){
    const modal=document.getElementById('assignment-modal');
    if(modal.parentElement!==document.body)document.body.appendChild(modal);
    const department=document.getElementById('assignment-department');
    const programme=document.getElementById('assignment-programme');
    const unit=document.getElementById('assignment-unit');
    const group=document.getElementById('assignment-group');
    const method=document.getElementById('assignment-method');
    const title=document.getElementById('assignment-modal-title');
    const context=document.getElementById('assignment-modal-context');
    const errors=document.getElementById('assignment-form-errors');
    const submit=document.getElementById('assignment-submit');
    const submitLabel=submit.querySelector('span');
    const mandatory=document.getElementById('assignment-mandatory');
    const periodFields=Array.from(assignmentForm.querySelectorAll('.assignment-period-field'));
    const parseData=value=>{try{return JSON.parse(value||'{}');}catch(error){return {};}};
    const syncProgrammes=selectedProgramme=>{
        const requested=Array.from(programme.options).find(option=>String(option.value)===String(selectedProgramme||''));
        if(department&&requested?.dataset.departmentId)department.value=requested.dataset.departmentId;
        Array.from(programme.options).forEach(option=>{
            const visible=!department||String(option.dataset.departmentId||'')===String(department.value||'');
            option.hidden=!visible;
            option.disabled=!visible;
        });
        const selected=Array.from(programme.options).find(option=>String(option.value)===String(selectedProgramme||'')&&!option.disabled)
            ||Array.from(programme.options).find(option=>!option.disabled);
        programme.value=selected?.value||'';
    };
    const updateAssignmentContext=()=>{
        const parts=[department?.selectedOptions[0]?.text,programme.options[programme.selectedIndex]?.text]
            .filter(value=>value&&value.trim());
        context.textContent=parts.join(' / ');
    };
    const syncUnits=selectedUnit=>{
        const owner=programme.options[programme.selectedIndex]?.dataset.ownerId;
        Array.from(unit.options).forEach(option=>{
            const ownerMatches=option.dataset.ownerId===owner||option.dataset.ownerId===@json((string)$root->id);
            const available=option.dataset.active==='1'||String(option.value)===String(selectedUnit||'');
            option.hidden=!(ownerMatches&&available);
            option.disabled=option.hidden;
        });
        const selected=Array.from(unit.options).find(option=>String(option.value)===String(selectedUnit||'')&&!option.disabled)
            ||Array.from(unit.options).find(option=>!option.disabled);
        unit.value=selected?.value||'';
    };
    const openAssignmentModal=(mode,data={})=>{
        assignmentForm.reset();
        errors.classList.add('d-none');
        errors.innerHTML='';
        const editing=mode==='edit';
        const programmeId=data.programme_node_id||data.programme_id||programme.options[0]?.value||'';
        syncProgrammes(programmeId);
        group.value=data.group_type||group.options[0]?.value||'';
        periodFields.forEach(field=>field.value=data[field.name]??'');
        assignmentForm.elements.display_order.value=data.display_order??0;
        assignmentForm.elements.credit_hours.value=data.credit_hours??'';
        mandatory.checked=data.is_mandatory===undefined?true:Boolean(data.is_mandatory);
        method.disabled=!editing;
        assignmentForm.action=editing
            ? assignmentForm.dataset.updateAction.replace('__ASSIGNMENT__',data.id)
            : assignmentForm.dataset.createAction.replace('__PROGRAMME__',programme.value);
        syncUnits(data.curriculum_unit_id);
        title.textContent=editing?@json(__('Edit unit assignment')):@json(__('Assign unit'));
        submitLabel.textContent=editing?@json(__('Save changes')):@json(__('Assign unit'));
        updateAssignmentContext();
        window.jQuery(modal).modal('show');
    };
    const contextModal=document.getElementById('assignment-context-modal');
    if(contextModal.parentElement!==document.body)document.body.appendChild(contextModal);
    const contextTitle=document.getElementById('assignment-context-title');
    const contextDescription=document.getElementById('assignment-context-description');
    const contextType=document.getElementById('assignment-context-type');
    const contextCount=document.getElementById('assignment-context-count');
    const contextRows=document.getElementById('assignment-context-rows');
    const contextEmpty=document.getElementById('assignment-context-empty');
    const contextAdd=document.getElementById('assignment-context-add');
    const groupLabels=@json($profileConfig['group_types'] ?? []);
    let activeContext=null;
    const escapeHtml=value=>String(value??'').replace(/[&<>'"]/g,character=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]));
    const assignmentPeriod=assignment=>['study_year','semester','module','stage','section']
        .map(field=>assignment[field]).filter(Boolean).join(' / ')||'—';
    const switchFromContext=(mode,data)=>{
        window.jQuery(contextModal).one('hidden.bs.modal',()=>openAssignmentModal(mode,data)).modal('hide');
    };
    const openContextModal=data=>{
        activeContext=data;
        const assignments=Array.isArray(data.assignments)?data.assignments:[];
        contextTitle.textContent=data.label||@json(__('Manage assigned units'));
        contextDescription.textContent=data.node_type==='unit_group'
            ? @json(__('Only units assigned to this unit group are shown.'))
            : @json(__('Only units assigned within this study period are shown.'));
        contextType.textContent=data.node_type==='unit_group'?@json(__('Unit Group')):@json(__('Study Period'));
        contextCount.textContent=assignments.length===1
            ? @json(__('1 assigned unit'))
            : `${assignments.length} ${@json(__('assigned units'))}`;
        contextRows.innerHTML=assignments.map((assignment,index)=>`<tr class="${assignment.active?'':'table-secondary'}"><td><strong>${escapeHtml(assignment.unit_name)}</strong></td><td>${escapeHtml(assignment.unit_code||'—')}</td><td>${escapeHtml(groupLabels[assignment.group_type]||assignment.group_type)}</td><td>${escapeHtml(assignmentPeriod(assignment))}</td><td><span class="badge ${assignment.active?'badge-success':'badge-secondary'}">${assignment.active?@json(__('Active')):@json(__('Inactive'))}</span></td><td class="text-right"><button type="button" class="btn btn-sm btn-outline-warning js-context-edit-assignment" data-index="${index}"><i class="fas fa-edit"></i> ${@json(__('Edit'))}</button></td></tr>`).join('');
        contextRows.closest('.table-responsive').classList.toggle('d-none',assignments.length===0);
        contextEmpty.classList.toggle('d-none',assignments.length!==0);
        contextRows.querySelectorAll('.js-context-edit-assignment').forEach(button=>button.addEventListener('click',()=>switchFromContext('edit',assignments[Number(button.dataset.index)])));
        window.jQuery(contextModal).modal('show');
    };
    contextAdd.addEventListener('click',()=>{
        if(!activeContext)return;
        switchFromContext('create',{...(activeContext.defaults||{}),programme_id:activeContext.programme_id});
    });
    document.querySelectorAll('.js-manage-assignment-context').forEach(button=>button.addEventListener('click',()=>openContextModal(parseData(button.dataset.context))));
    document.querySelectorAll('.js-open-assignment-modal').forEach(button=>button.addEventListener('click',()=>{
        const mode=button.dataset.mode||'create';
        const data=mode==='edit'?parseData(button.dataset.assignment):parseData(button.dataset.defaults);
        if(mode!=='edit')data.programme_id=button.dataset.programmeId||data.programme_id;
        openAssignmentModal(mode,data);
    }));
    department?.addEventListener('change',()=>{
        syncProgrammes();
        if(method.disabled)assignmentForm.action=assignmentForm.dataset.createAction.replace('__PROGRAMME__',programme.value);
        syncUnits();
        updateAssignmentContext();
    });
    programme.addEventListener('change',()=>{
        if(method.disabled)assignmentForm.action=assignmentForm.dataset.createAction.replace('__PROGRAMME__',programme.value);
        updateAssignmentContext();
        syncUnits(unit.value);
    });
    assignmentForm.addEventListener('submit',async event=>{
        event.preventDefault();
        errors.classList.add('d-none');
        submit.disabled=true;
        try{
            const response=await fetch(assignmentForm.action,{method:'POST',body:new FormData(assignmentForm),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
            const payload=await response.json();
            if(!response.ok){
                const messages=Object.values(payload.errors||{}).flat();
                errors.innerHTML=messages.map(message=>`<div>${String(message).replace(/[&<>'"]/g,character=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]))}</div>`).join('')||@json(__('The assignment could not be saved.'));
                errors.classList.remove('d-none');
                return;
            }
            sessionStorage.setItem(assignmentScrollKey,String(window.scrollY));
            window.location.assign(@json(route('admin.taxonomy.profiles.show', $profile)));
        }catch(error){
            errors.textContent=@json(__('The assignment could not be saved. Please try again.'));
            errors.classList.remove('d-none');
        }finally{submit.disabled=false;}
    });
    const initialAssignment=@json($editingAssignmentPayload);
    if(initialAssignment)openAssignmentModal('edit',initialAssignment);
}
document.querySelectorAll('.taxonomy-unassign-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Deactivate this unit assignment?'))))event.preventDefault();}));
document.querySelectorAll('.taxonomy-archive-unit-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Archive this canonical unit? Existing assignments and resources will be preserved but hidden from product forms.'))))event.preventDefault();}));
document.querySelectorAll('.taxonomy-purge-unit-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Permanently delete this canonical unit? This cannot be undone and is blocked while assignments or products still use it.'))))event.preventDefault();}));
document.querySelectorAll('.taxonomy-archive-node-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Archive this taxonomy branch?'))))event.preventDefault();}));
document.querySelectorAll('.taxonomy-purge-form').forEach(form=>form.addEventListener('submit',event=>{if(form.id==='bulk-purge-profile-form'&&document.querySelectorAll('.profile-archived-node-checkbox:checked').length===0){event.preventDefault();alert(@json(__('Select at least one archived taxonomy node.')));return;}if(!confirm(@json(__('Permanently delete the selected archived taxonomy data? This cannot be undone.'))))event.preventDefault();}));
const archivedSelectAll=document.getElementById('profile-archived-select-all');if(archivedSelectAll){archivedSelectAll.addEventListener('change',()=>document.querySelectorAll('.profile-archived-node-checkbox').forEach(checkbox=>checkbox.checked=archivedSelectAll.checked));}
</script>
@endpush
