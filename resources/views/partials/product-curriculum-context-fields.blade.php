@php
    $curriculumOptions = $curriculumOptions ?? ['profiles' => [], 'taxonomy_paths' => [], 'assignments' => [], 'units' => [], 'selected' => []];
    $curriculumAdmin = $curriculumAdmin ?? false;
    $selectedProfile = (string) ($curriculumOptions['selected']['profile'] ?? '');
    $selectedAssignment = (string) ($curriculumOptions['selected']['assignment_id'] ?? '');
    $selectedUnit = (string) ($curriculumOptions['selected']['unit_id'] ?? '');
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

@if (!empty($curriculumOptions['profiles']))
    <div class="curriculum-path-card" data-curriculum-context data-selected-assignment="{{ $selectedAssignment }}" data-selected-profile="{{ $selectedProfile }}" hidden>
        <input type="hidden" name="curriculum_profile" data-curriculum-profile value="{{ $selectedProfile }}">
        @unless($curriculumAdmin)
            <input type="hidden" name="curriculum_unit_id" data-curriculum-unit value="{{ $selectedUnit }}">
        @endunless
        <script type="application/json" data-curriculum-profiles>@json($curriculumOptions['profiles'])</script>
        <script type="application/json" data-curriculum-taxonomy-paths>@json($curriculumOptions['taxonomy_paths'] ?? [])</script>
        <script type="application/json" data-curriculum-assignments>@json($curriculumOptions['assignments'])</script>

        <div class="curriculum-path-card__header">
            <div class="curriculum-path-card__icon"><i class="fas fa-sitemap"></i></div>
            <div>
                <div class="curriculum-path-card__title-row">
                    <strong>{{ __('Higher Education Classification') }}</strong>
                    <span class="curriculum-path-card__badge" data-curriculum-profile-label></span>
                </div>
                <p>{{ __('Choose each level in order. The next field only shows options valid for the path already selected.') }}</p>
                <div class="curriculum-path-card__architecture" data-curriculum-architecture></div>
            </div>
        </div>

        <div class="curriculum-path-card__trail" data-curriculum-trail aria-live="polite"></div>
        <div class="curriculum-path-grid" data-curriculum-fields>
            @foreach (['owner', 'department', 'programme', 'study_year', 'semester', 'module', 'stage', 'section', 'group', 'assignment'] as $field)
                <div class="curriculum-path-field form-grp paper-field note-field" data-curriculum-field="{{ $field }}" hidden>
                    <label for="curriculum_{{ $field }}" data-curriculum-label>{{ __((string) str($field)->headline()) }}</label>
                    <select id="curriculum_{{ $field }}" @if($field === 'assignment') name="programme_unit_assignment_id" @endif class="form-control form-select" data-curriculum-select="{{ $field }}">
                        <option value="">{{ __('Select an option') }}</option>
                    </select>
                    @if($field === 'assignment')
                        @error('programme_unit_assignment_id')<small class="text-danger paper-error note-error">{{ $message }}</small>@enderror
                    @endif
                </div>
            @endforeach
        </div>

        <div class="curriculum-path-card__empty" data-curriculum-empty hidden>
            <i class="fas fa-info-circle"></i>
            <span>{{ __('No active programme units have been configured for this education level yet.') }}</span>
        </div>

        @if ($curriculumAdmin)
            <details class="curriculum-path-card__advanced">
                <summary>{{ __('Advanced: publish as a shared canonical unit') }}</summary>
                <p>{{ __('Use this only when the same resource should appear under every programme to which the unit is assigned.') }}</p>
                <div class="curriculum-path-field form-grp paper-field note-field">
                    <label for="curriculum_unit_id">{{ __('Shared Canonical Unit') }}</label>
                    <select id="curriculum_unit_id" name="curriculum_unit_id" class="form-control form-select" data-shared-unit>
                        <option value="">{{ __('Use the programme-specific path above') }}</option>
                        @foreach ($curriculumOptions['units'] as $unit)
                            <option value="{{ $unit['id'] }}" data-profile="{{ $unit['profile'] }}" @selected($selectedUnit === (string) $unit['id'] && $selectedAssignment === '')>{{ $unit['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </details>
        @endif

        <div class="curriculum-path-card__assessment">
            <div class="form-grp paper-field note-field">
                <label for="assessment_series">{{ __('Assessment Series') }}</label>
                <input id="assessment_series" name="assessment_series" class="form-control" value="{{ old('assessment_series', $curriculumOptions['selected']['assessment_series'] ?? '') }}" placeholder="{{ __('e.g. July or November') }}">
            </div>
            <div class="form-grp paper-field note-field">
                <label for="paper_number">{{ __('Paper / Variant') }}</label>
                <input id="paper_number" name="paper_number" class="form-control" value="{{ old('paper_number', $curriculumOptions['selected']['paper_number'] ?? '') }}" placeholder="{{ __('e.g. Paper 1') }}">
            </div>
        </div>
    </div>

    @once
        <style>
            .curriculum-path-card{grid-column:1/-1;border:1px solid #d9e2ec;border-radius:16px;padding:20px;background:linear-gradient(145deg,#fff 0%,#f8fafc 100%);margin:12px 0;box-shadow:0 8px 24px rgba(15,23,42,.05)}
            .curriculum-path-card__header{display:flex;gap:13px;align-items:flex-start;margin-bottom:16px}.curriculum-path-card__icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:var(--curriculum-accent,#334155);color:#fff;flex:0 0 auto}.curriculum-path-card__title-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.curriculum-path-card__title-row strong{font-size:16px;color:#0f172a}.curriculum-path-card__header p,.curriculum-path-card__advanced p{margin:4px 0 0;color:#64748b;font-size:13px}.curriculum-path-card__architecture{margin-top:7px;color:var(--curriculum-accent,#334155);font-size:12px;font-weight:700}.curriculum-path-card__badge{padding:3px 9px;border-radius:999px;background:#eef2ff;color:var(--curriculum-accent,#334155);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
            .curriculum-path-card__trail{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:16px}.curriculum-path-card__trail:empty{display:none}.curriculum-path-card__crumb{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border:1px solid #dbe4ee;border-radius:8px;background:#fff;color:#334155;font-size:12px}.curriculum-path-card__crumb:not(:last-child)::after{content:'›';margin-left:4px;color:#94a3b8;font-weight:700}
            .curriculum-path-grid,.curriculum-path-card__assessment{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}.curriculum-path-field label{display:block;margin-bottom:6px;font-weight:600;color:#334155}.curriculum-path-field select:disabled{background:#f1f5f9;color:#94a3b8}.curriculum-path-card__empty{padding:12px 14px;border-radius:10px;background:#fff7ed;color:#9a3412;font-size:13px;margin-top:12px}.curriculum-path-card__empty i{margin-right:7px}
            .curriculum-path-card__advanced{margin-top:16px;padding:12px 14px;border:1px dashed #cbd5e1;border-radius:10px;background:#fff}.curriculum-path-card__advanced summary{cursor:pointer;color:#475569;font-weight:600;font-size:13px}.curriculum-path-card__advanced .curriculum-path-field{margin-top:12px}.curriculum-path-card__assessment{margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0}
            @media(max-width:767px){.curriculum-path-card{padding:15px}.curriculum-path-grid,.curriculum-path-card__assessment{grid-template-columns:1fr}}
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const aliases = {cdacc:['cdacc'],tvet:['tvet'],certificate:['certificate'],diploma:['diploma'],undergraduate:['undergraduate','university'],professional:['professional']};
                const dimensions = {
                    owner:{value:'owner_id',text:'owner'},department:{value:'department_id',text:'department'},programme:{value:'programme_id',text:'programme_label'},
                    study_year:{value:'study_year',text:'study_year'},semester:{value:'semester',text:'semester'},module:{value:'module',text:'module'},
                    stage:{value:'stage',text:'stage'},section:{value:'section',text:'section'},group:{value:'group',text:'group'},assignment:{value:'id',text:'unit_label'}
                };
                const fallbackLabels = {owner:'{{ __('Institution / Awarding Body') }}',department:'{{ __('School / Department') }}',programme:'{{ __('Programme') }}',study_year:'{{ __('Year') }}',semester:'{{ __('Semester') }}',module:'{{ __('Module') }}',stage:'{{ __('Stage') }}',section:'{{ __('Section') }}',group:'{{ __('Unit Group') }}',assignment:'{{ __('Unit') }}'};
                const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));

                document.querySelectorAll('[data-curriculum-context]').forEach(panel => {
                    const profiles = JSON.parse(panel.querySelector('[data-curriculum-profiles]').textContent || '[]');
                    const taxonomyPaths = JSON.parse(panel.querySelector('[data-curriculum-taxonomy-paths]').textContent || '[]');
                    const records = JSON.parse(panel.querySelector('[data-curriculum-assignments]').textContent || '[]');
                    const profileInput = panel.querySelector('[data-curriculum-profile]');
                    const profileLabel = panel.querySelector('[data-curriculum-profile-label]');
                    const architecture = panel.querySelector('[data-curriculum-architecture]');
                    const educationLevel = panel.closest('form')?.querySelector('#education_level');
                    const assignmentSelect = panel.querySelector('[data-curriculum-select="assignment"]');
                    const unitInput = panel.querySelector('[data-curriculum-unit]');
                    const sharedUnit = panel.querySelector('[data-shared-unit]');
                    const trail = panel.querySelector('[data-curriculum-trail]');
                    const empty = panel.querySelector('[data-curriculum-empty]');
                    const legacyFields = ['class_grade','subject','exam_category'].map(id => panel.closest('form')?.querySelector(`#${id}`)).filter(Boolean);
                    const selectedAssignmentId = String(panel.dataset.selectedAssignment || '');
                    const selectedRecord = records.find(record => String(record.id) === selectedAssignmentId) || null;
                    let initializing = true;

                    const profileFromEducation = () => {
                        const level = String(educationLevel?.value || '').toLowerCase();
                        return Object.entries(aliases).find(([,values]) => values.some(value => level.includes(value)))?.[0] || '';
                    };
                    const fieldLabel = (field, profile) => (profile.labels?.[field] || {owner:profile.labels?.awarding_body,department:profile.labels?.department,programme:profile.labels?.programme,assignment:profile.labels?.unit}[field] || fallbackLabels[field]);
                    const choiceLabel = (field, record, profile) => field === 'group' ? (profile.group_types?.[record.group] || String(record.group || '').replaceAll('_',' ')) : (record[dimensions[field].text] || '');
                    const uniqueChoices = (field, candidates, profile) => {
                        const choices = new Map();
                        candidates.forEach(record => {
                            const value = record[dimensions[field].value];
                            if (value !== null && value !== undefined && String(value) !== '') choices.set(String(value), choiceLabel(field, record, profile));
                        });
                        return Array.from(choices, ([value,label]) => ({value,label}));
                    };
                    const fillSelect = (select, choices, preferred, label) => {
                        select.innerHTML = '';
                        select.add(new Option(`{{ __('Select') }} ${label}`, ''));
                        choices.forEach(choice => select.add(new Option(choice.label, choice.value)));
                        const valid = choices.some(choice => choice.value === String(preferred || ''));
                        select.value = valid ? String(preferred) : (choices.length === 1 ? choices[0].value : '');
                        select.disabled = choices.length === 0;
                    };
                    const updateTrail = fields => {
                        const parts = fields.map(field => panel.querySelector(`[data-curriculum-select="${field}"]`)?.selectedOptions[0]).filter(option => option?.value).map(option => option.textContent.trim());
                        trail.innerHTML = parts.map(part => `<span class="curriculum-path-card__crumb">${escapeHtml(part)}</span>`).join('');
                    };
                    const configure = (changedField = null) => {
                        const profile = profiles.find(item => item.key === profileInput.value);
                        panel.hidden = !profile;
                        legacyFields.forEach(field => {
                            const wrapper = field.closest('.paper-field,.note-field,.form-grp');
                            if (wrapper) wrapper.hidden = !!profile;
                            field.disabled = !!profile;
                        });
                        if (!profile) return;
                        panel.style.setProperty('--curriculum-accent', profile.accent || '#334155');
                        profileLabel.textContent = profile.label;
                        const activeFields = profile.creation_fields || ['owner','department','programme','assignment'];
                        architecture.textContent = activeFields.map(field => fieldLabel(field, profile)).join(' → ');
                        panel.querySelectorAll('[data-curriculum-field]').forEach(wrapper => {
                            const field = wrapper.dataset.curriculumField;
                            wrapper.hidden = !activeFields.includes(field);
                            const label = wrapper.querySelector('[data-curriculum-label]');
                            if (label) label.textContent = fieldLabel(field, profile);
                        });
                        let pathCandidates = taxonomyPaths.filter(path => path.profile === profile.key);
                        let candidates = records.filter(record => record.profile === profile.key);
                        let clearFollowing = false;
                        let pathComplete = true;
                        activeFields.forEach(field => {
                            const select = panel.querySelector(`[data-curriculum-select="${field}"]`);
                            const preferred = initializing && selectedRecord ? selectedRecord[dimensions[field].value] : (clearFollowing ? '' : select.value);
                            const isStructuralField = ['owner','department','programme'].includes(field);
                            const choices = !pathComplete ? [] : (field === 'group'
                                ? Object.entries(profile.group_types || {}).map(([value,label]) => ({value,label}))
                                : uniqueChoices(field, isStructuralField ? pathCandidates : candidates, profile));
                            fillSelect(select, choices, preferred, fieldLabel(field, profile));
                            if (field === changedField) clearFollowing = true;
                            if (select.value) {
                                if (isStructuralField) {
                                    pathCandidates = pathCandidates.filter(path => String(path[dimensions[field].value] ?? '') === select.value);
                                }
                                candidates = candidates.filter(record => String(record[dimensions[field].value]) === select.value);
                            } else {
                                pathCandidates = [];
                                candidates = [];
                                pathComplete = false;
                            }
                        });
                        const selectedProgramme = panel.querySelector('[data-curriculum-select="programme"]')?.value;
                        empty.hidden = !selectedProgramme || records.some(record => record.profile === profile.key && String(record.programme_id) === selectedProgramme);
                        Array.from(sharedUnit?.options || []).forEach((option,index) => { if (index > 0) option.hidden = option.dataset.profile !== profile.key; });
                        if (sharedUnit?.selectedOptions[0]?.hidden) sharedUnit.value = '';
                        const selectedAssignment = records.find(record => String(record.id) === String(assignmentSelect.value));
                        if (unitInput) unitInput.value = selectedAssignment?.unit_id || '';
                        updateTrail(activeFields);
                    };
                    const syncProfile = () => {
                        profileInput.value = selectedRecord?.profile || profileFromEducation() || panel.dataset.selectedProfile || '';
                        configure();
                        initializing = false;
                    };

                    panel.querySelectorAll('[data-curriculum-select]').forEach(select => select.addEventListener('change', () => {
                        initializing = false;
                        if (select === assignmentSelect && select.value && sharedUnit) sharedUnit.value = '';
                        configure(select.dataset.curriculumSelect);
                    }));
                    sharedUnit?.addEventListener('change', () => {
                        if (sharedUnit.value) {
                            assignmentSelect.value = '';
                            updateTrail((profiles.find(item => item.key === profileInput.value)?.creation_fields || []).filter(field => field !== 'assignment'));
                        }
                    });
                    educationLevel?.addEventListener('change', () => {
                        initializing = false;
                        profileInput.value = profileFromEducation();
                        configure();
                    });
                    syncProfile();
                });
            });
        </script>
    @endonce
@endif
