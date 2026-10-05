<script>
    (function () {
        const root = document.querySelector('[data-note-wizard]');

        if (!root) {
            return;
        }

        const storageKey = 'revisionhub.note.wizard';
        const step = Number(root.dataset.noteWizardStep || 1);
        const defaults = (() => {
            try {
                return JSON.parse(root.dataset.noteWizardState || '{}');
            } catch (error) {
                return {};
            }
        })();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const stepperItems = Array.from(root.querySelectorAll('.note-stepper__item[data-note-step-url]'));

        const readState = () => {
            try {
                return JSON.parse(window.localStorage.getItem(storageKey) || '{}');
            } catch (error) {
                return {};
            }
        };

        const writeState = (nextState) => {
            window.localStorage.setItem(storageKey, JSON.stringify(nextState));
        };

        const deepMerge = (base, override) => {
            const output = Array.isArray(base) ? [...base] : { ...base };

            Object.entries(override || {}).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    output[key] = [...value];
                    return;
                }

                if (value && typeof value === 'object') {
                    output[key] = deepMerge(base?.[key] || {}, value);
                    return;
                }

                output[key] = value;
            });

            return output;
        };

        const navigateToStep = (url) => {
            if (!url) {
                return;
            }

            window.location.assign(url);
        };

        const scrollActiveStepperItemIntoView = () => {
            if (window.innerWidth >= 992) {
                return;
            }

            const activeItem = root.querySelector('.note-stepper__item.is-active[data-note-step-url]');

            activeItem?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center',
            });
        };

        const formatBytes = (bytes) => {
            if (!Number.isFinite(bytes)) {
                return '';
            }

            const mb = bytes / (1024 * 1024);

            return mb >= 1 ? `${mb.toFixed(mb >= 10 ? 1 : 2)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
        };

        const parseSizeToBytes = (size) => {
            if (typeof size === 'number' && Number.isFinite(size)) {
                return size;
            }

            const raw = String(size || '').trim().toLowerCase();
            const match = raw.match(/^([\d.]+)\s*(kb|mb|gb)?$/i);

            if (!match) {
                return 0;
            }

            const value = Number(match[1]);
            const unit = (match[2] || 'b').toLowerCase();

            if (!Number.isFinite(value)) {
                return 0;
            }

            if (unit === 'gb') return value * 1024 * 1024 * 1024;
            if (unit === 'mb') return value * 1024 * 1024;
            if (unit === 'kb') return value * 1024;

            return value;
        };

        const fileIconClass = (fileName = '') => {
            const extension = String(fileName).split('.').pop().toLowerCase();

            if (['doc', 'docx'].includes(extension)) return 'fas fa-file-word';
            if (['ppt', 'pptx'].includes(extension)) return 'fas fa-file-powerpoint';
            if (['zip', 'rar', '7z'].includes(extension)) return 'fas fa-file-archive';
            if (['mp4', 'mov', 'webm'].includes(extension)) return 'fas fa-file-video';
            if (['xls', 'xlsx', 'csv'].includes(extension)) return 'fas fa-file-excel';
            if (extension === 'pdf') return 'fas fa-file-pdf';

            return 'fas fa-file-alt';
        };

        const allowedAttachmentExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'mp4', 'mov', 'webm'];
        const allowedAttachmentMimePrefixes = ['application/pdf', 'application/msword', 'application/vnd', 'video/'];

        const attachmentTypeLabel = (resource = {}) => {
            const rawType = String(resource.resource_type || '').toLowerCase();
            const path = String(resource.url_or_path || resource.title || '').toLowerCase();
            const extension = path.split('.').pop();

            if (rawType === 'pdf' || extension === 'pdf') return 'PDF';
            if (['doc', 'docx'].includes(rawType) || ['doc', 'docx'].includes(extension)) return 'DOC';
            if (['ppt', 'pptx'].includes(rawType) || ['ppt', 'pptx'].includes(extension)) return 'PPT';
            if (rawType === 'archive' || ['zip', 'rar', '7z'].includes(extension)) return 'ZIP';
            if (rawType === 'video' || ['mp4', 'mov', 'webm'].includes(extension)) return 'VIDEO';

            return 'FILE';
        };

        const attachmentTypeFromFile = (file = {}) => {
            const name = String(file.name || '').toLowerCase();
            const mime = String(file.type || '').toLowerCase();
            const extension = name.split('.').pop();

            if (extension === 'pdf' || mime === 'application/pdf') return 'PDF';
            if (['doc', 'docx'].includes(extension) || mime.includes('word')) return 'DOC';
            if (['ppt', 'pptx'].includes(extension) || mime.includes('presentation')) return 'PPT';
            if (['zip', 'rar', '7z'].includes(extension) || mime.includes('zip')) return 'ZIP';
            if (['mp4', 'mov', 'webm'].includes(extension) || mime.startsWith('video/')) return 'VIDEO';

            return 'FILE';
        };

        const validateAttachmentFile = (file = {}) => {
            const name = String(file.name || '');
            const mime = String(file.type || '').toLowerCase();
            const extension = name.split('.').pop().toLowerCase();

            if (!extension || !allowedAttachmentExtensions.includes(extension)) {
                return { valid: false, message: `${name || 'This file'} is not an accepted note attachment.` };
            }

            const mimeAllowed = allowedAttachmentMimePrefixes.some((prefix) => mime.startsWith(prefix));

            if (!mimeAllowed && mime && !mime.startsWith('application/octet-stream')) {
                // Some browsers provide generic mime types; allow if extension is valid.
            }

            const maxBytes = 50 * 1024 * 1024;

            if (Number.isFinite(file.size) && file.size > maxBytes) {
                return { valid: false, message: `${name || 'This file'} is larger than 50 MB.` };
            }

            return { valid: true, message: '' };
        };

        const calculateResourceSummary = (resources = []) => {
            const bytes = resources.reduce((total, resource) => {
                if (Number.isFinite(resource.size_bytes)) {
                    return total + resource.size_bytes;
                }

                return total + parseSizeToBytes(resource.size);
            }, 0);

            return {
                count: resources.length,
                totalSize: bytes > 0 ? formatBytes(bytes) : '0 KB',
            };
        };

        const resolveDownloadUrl = (resource = {}) => {
            if (resource.download_url) {
                return resource.download_url;
            }

            const path = String(resource.url_or_path || '').trim();

            if (!path) {
                return '#';
            }

            if (/^https?:\/\//i.test(path) || path.startsWith('//')) {
                return path;
            }

            return `/${path.replace(/^\/+/, '')}`;
        };

        const calculateCurriculumSummary = (curriculum = []) => {
            const chapters = (curriculum || []).filter((node) => (node?.type || '') === 'chapter');
            const pages = chapters.reduce((total, chapter) => total + (Number.parseInt(chapter.pages, 10) || 0), 0);

            return {
                chapters: chapters.length,
                pages,
                words: pages > 0 ? Math.max(0, pages * 300).toLocaleString() : '0',
                includes_examples: chapters.length > 0,
                includes_past_questions: chapters.length > 0,
                includes_formulas: chapters.length > 0,
                includes_diagrams: chapters.length > 0,
            };
        };

        const createReadingStub = (chapterTitle, html = '<p></p>') => ({
            type: 'reading',
            title: `${chapterTitle || 'Chapter'} Reading`,
            is_published: true,
            blocks: [
                {
                    type: 'rich_text',
                    content: {
                        html,
                    },
                },
            ],
        });

        const createTopicStub = (chapterTitle, topicTitle = null, html = '<p></p>') => ({
            type: 'topic',
            title: topicTitle || `${chapterTitle || 'Chapter'} Section`,
            is_published: true,
            children: [createReadingStub(topicTitle || chapterTitle, html)],
        });

        const stripHtml = (html = '') => String(html)
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        const previewText = (html = '', fallback = 'Type content here to start drafting this chapter.') => {
            const text = stripHtml(html);
            return text ? text.slice(0, 180) : fallback;
        };

        const normalizeNotePrice = (value) => {
            const numeric = Number.parseFloat(String(value ?? '').replace(/[^0-9.-]/g, '').trim());

            return Number.isFinite(numeric) ? numeric : null;
        };

        const formatNotePrice = (value) => {
            const numeric = normalizeNotePrice(value);

            if (numeric === null) {
                return '';
            }

            return `KES ${numeric.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`;
        };

        const syncNotePriceDisplays = (value) => {
            const displayValue = formatNotePrice(value);
            const hiddenValue = value ?? '';

            document.querySelectorAll('[data-note-price-display]').forEach((element) => {
                if (displayValue || hiddenValue === '') {
                    element.textContent = displayValue;
                }
            });

            document.querySelectorAll('[data-note-price-input]').forEach((input) => {
                input.value = hiddenValue;
            });
        };

        const extractReadingHtml = (topic = {}) => {
            const readingNode = Array.isArray(topic.children)
                ? topic.children.find((child) => (child?.type || '') === 'reading')
                : null;

            const richTextBlock = Array.isArray(readingNode?.blocks)
                ? readingNode.blocks.find((block) => (block?.type || '') === 'rich_text')
                : null;

            return richTextBlock?.content?.html || '<p></p>';
        };

        const normalizeReading = (reading = {}, topicTitle = '') => {
            const html = reading?.blocks?.find?.((block) => (block?.type || '') === 'rich_text')?.content?.html || '<p></p>';

            return {
                ...reading,
                type: 'reading',
                title: reading.title || `${topicTitle || 'Section'} Reading`,
                is_published: reading.is_published !== false,
                blocks: [
                    {
                        type: 'rich_text',
                        content: {
                            html,
                        },
                    },
                ],
            };
        };

        const normalizeTopic = (topic = {}, chapterTitle = '') => {
            const title = String(topic.title || '').trim();
            const html = extractReadingHtml(topic);

            return {
                ...topic,
                type: 'topic',
                title,
                is_published: topic.is_published !== false,
                children: Array.isArray(topic.children) && topic.children.length
                    ? topic.children.map((child) => (child?.type === 'reading' ? normalizeReading(child, title || chapterTitle) : child))
                    : [createReadingStub(title || chapterTitle, html)],
            };
        };

        const normalizeChapter = (chapter = {}) => {
            const title = String(chapter.title || '').trim();
            const pages = Math.max(1, Number.parseInt(chapter.pages, 10) || 1);
            const contentHtml = String(chapter.content_html || '').trim();
            const children = Array.isArray(chapter.children) && chapter.children.length
                ? chapter.children.map((topic) => normalizeTopic(topic, title))
                : [];

            return {
                ...chapter,
                type: 'chapter',
                title,
                pages,
                is_published: chapter.is_published !== false,
                content_html: contentHtml,
                ui_topics_expanded: chapter.ui_topics_expanded === true,
                children,
            };
        };

        let state = deepMerge(defaults, readState());

        const get = (path, fallback = null) => {
            return path.split('.').reduce((accumulator, segment) => (
                accumulator && Object.prototype.hasOwnProperty.call(accumulator, segment)
                    ? accumulator[segment]
                    : undefined
            ), state) ?? fallback;
        };

        const set = (path, value) => {
            const segments = path.split('.');
            let cursor = state;

            while (segments.length > 1) {
                const segment = segments.shift();
                cursor[segment] = cursor[segment] || {};
                cursor = cursor[segment];
            }

            cursor[segments[0]] = value;
            writeState(state);
        };

        const setCheckbox = (selector, value) => {
            const input = document.querySelector(selector);
            if (input) {
                input.checked = Boolean(value);
            }
        };

        const fillInput = (selector, value) => {
            const input = document.querySelector(selector);
            if (input && value !== undefined && value !== null) {
                input.value = value;
            }
        };

        const noteEducationTree = @json($educationTree ?? $paperEducationCategories ?? $educationCategories ?? $categories ?? []);
        const classGradeLabel = document.querySelector('label[for="class_grade"]');
        const classGradeSelect = document.querySelector('#class_grade');
        const subjectSelect = document.querySelector('#subject');
        const schoolOfLevelPattern = /tvet|university|college|certificate|diploma|undergraduate|tertiary|higher education/i;
        const normalizeLookupLabel = (value) => String(value || '')
            .trim()
            .replace(/\s+/g, ' ')
            .toLowerCase();
        const findNodeByLabel = (nodes, label) => {
            const normalizedLabel = normalizeLookupLabel(label);

            for (const node of nodes || []) {
                const nodeLabel = node?.label || node?.name || node?.title || '';
                const nodeSlug = node?.slug || '';

                if (normalizeLookupLabel(nodeLabel) === normalizedLabel || normalizeLookupLabel(nodeSlug) === normalizedLabel) {
                    return node;
                }

                if (Array.isArray(node?.children) && node.children.length) {
                    const match = findNodeByLabel(node.children, label);
                    if (match) {
                        return match;
                    }
                }
            }

            return null;
        };
        const resolveClassGrades = (educationLevel) => {
            const levelNode = findNodeByLabel(noteEducationTree, educationLevel);
            const children = Array.isArray(levelNode?.children) ? levelNode.children : [];

            if (children.length) {
                return children.map((child) => child?.label || child?.name || child?.title || '').filter(Boolean);
            }

            return [];
        };

        const resolveSubjectOptions = (educationLevel, classGrade) => {
            const levelNode = findNodeByLabel(noteEducationTree, educationLevel);
            const classGradeNode = levelNode ? findNodeByLabel(levelNode.children ?? [], classGrade) : null;
            const children = Array.isArray(classGradeNode?.children) ? classGradeNode.children : [];

            return children
                .map((child) => child?.label || child?.name || child?.title || '')
                .filter(Boolean);
        };

        const populateClassGradeOptions = (educationLevel, preferredValue = '') => {
            if (!classGradeSelect) {
                return;
            }

            const options = resolveClassGrades(educationLevel);
            const previousValue = preferredValue || classGradeSelect.value;
            const placeholderText = @json(__('None'));

            classGradeSelect.innerHTML = '';
            classGradeSelect.appendChild(new Option(placeholderText, '', false, !previousValue));

            options.forEach((option) => {
                const optionEl = document.createElement('option');
                optionEl.value = option;
                optionEl.textContent = option;
                optionEl.selected = previousValue === option;
                classGradeSelect.appendChild(optionEl);
            });

            if (options.includes(previousValue)) {
                classGradeSelect.value = previousValue;
            } else if (options.length) {
                classGradeSelect.value = options[0];
            } else {
                classGradeSelect.value = '';
            }
        };

        const populateSubjectOptions = (educationLevel, classGrade, preferredValue = '') => {
            if (!subjectSelect) {
                return;
            }

            const options = resolveSubjectOptions(educationLevel, classGrade);
            const previousValue = preferredValue || subjectSelect.value;
            const placeholderText = @json(__('None'));

            subjectSelect.innerHTML = '';
            subjectSelect.appendChild(new Option(placeholderText, '', false, !previousValue));

            options.forEach((option) => {
                const optionEl = document.createElement('option');
                optionEl.value = option;
                optionEl.textContent = option;
                optionEl.selected = previousValue === option;
                subjectSelect.appendChild(optionEl);
            });

            if (options.includes(previousValue)) {
                subjectSelect.value = previousValue;
            } else if (options.length) {
                subjectSelect.value = options[0];
            } else {
                subjectSelect.value = '';
            }
        };

        const updateClassGradeLabel = () => {
            if (!classGradeLabel) {
                return;
            }

            const educationLevel = document.querySelector('#education_level')?.value || '';
            const label = schoolOfLevelPattern.test(String(educationLevel))
                ? '{{ __('School of') }}'
                : '{{ __('Class / Grade') }}';

            classGradeLabel.childNodes[0].textContent = `${label} `;
        };

        const fillStepOne = () => {
            fillInput('#title', get('note.title', ''));
            fillInput('#description', get('note.description', ''));
            const educationLevel = get('note.education_level', 'Senior School');
            const classGrade = get('note.class_grade', 'Grade 10');
            const subject = get('note.subject', 'STEM');
            fillInput('#education_level', educationLevel);
            populateClassGradeOptions(educationLevel, classGrade);
            fillInput('#class_grade', classGrade);
            populateSubjectOptions(educationLevel, classGrade, subject);
            fillInput('#subject', subject);
            fillInput('#exam_category', get('note.exam_category', 'KCPE'));
            fillInput('#topic', get('note.topic', 'Algebra'));
            fillInput('#sub_topic', get('note.sub_topic', ''));
            fillInput('#tags', Array.isArray(get('note.tags', [])) ? get('note.tags', []).join(', ') : get('note.tags', ''));
            fillInput('#price', get('note.price', ''));
            syncNotePriceDisplays(get('note.price', ''));
            fillInput('#language', get('note.language', 'English'));
            fillInput('#access_type', get('note.access_type', 'paid'));
            fillInput('input[name="access_type"][value="paid"]', null);
            fillInput('input[name="access_type"][value="free"]', null);
            fillCheckboxGroup('access_type', get('note.access_type', 'paid'));
            updateClassGradeLabel();
        };

        const fillCheckboxGroup = (name, value) => {
            document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
                input.checked = input.value === value;
            });
        };

        const fillStepFour = () => {
            const switches = Array.from(root.querySelectorAll('.note-switch input'));

            if (switches[0]) switches[0].checked = Boolean(get('settings.allow_download', true));
            if (switches[1]) switches[1].checked = Boolean(get('settings.add_to_bundle', true));
            if (switches[2]) switches[2].checked = Boolean(get('settings.featured_note', true));
            if (switches[3]) switches[3].checked = Boolean(get('settings.allow_comments', true));

            setCheckbox('input[name="visibility"][value="public"]', get('settings.visibility', 'public') === 'public');
            setCheckbox('input[name="visibility"][value="private"]', get('settings.visibility', 'public') === 'private');

            fillInput('#meta_title', get('settings.meta_title', ''));
            fillInput('#meta_description', get('settings.meta_description', ''));
            fillInput('#keywords', get('settings.keywords', ''));
            fillInput('#sort_order', get('settings.sort_order', 10));
        };

        const updateCurriculum = (nextCurriculum) => {
            state.curriculum = (nextCurriculum || []).map(normalizeChapter);
            writeState(state);
        };

        const sectionRowsFromChapter = (chapter = {}) => {
            const topics = Array.isArray(chapter.children) ? chapter.children : [];
            const rows = topics
                .map((topic) => ({
                    title: String(topic?.title || '').trim(),
                    content: extractReadingHtml(topic),
                }))
                .filter((row) => row.title || row.content);

            return rows.length ? rows : [{
                title: chapter.title ? `${chapter.title} Section` : 'Section 1',
                content: '<p></p>',
            }];
        };

        const bindStepOne = () => {
            const bindings = [
                ['#title', 'note.title', 'input'],
                ['#description', 'note.description', 'input'],
                ['#education_level', 'note.education_level', 'change'],
                ['#class_grade', 'note.class_grade', 'change'],
                ['#exam_category', 'note.exam_category', 'change'],
                ['#subject', 'note.subject', 'change'],
                ['#topic', 'note.topic', 'input'],
                ['#sub_topic', 'note.sub_topic', 'input'],
                ['#tags', 'note.tags', 'input'],
                ['#price', 'note.price', 'input'],
                ['#language', 'note.language', 'change'],
            ];

            bindings.forEach(([selector, path, eventName]) => {
                const input = document.querySelector(selector);
                if (!input) {
                    return;
                }

                input.addEventListener(eventName, () => {
                    const value = selector === '#tags'
                        ? input.value.split(',').map((tag) => tag.trim()).filter(Boolean)
                        : input.value;

                    set(path, value);
                    if (selector === '#price') {
                        syncNotePriceDisplays(value);
                    }

                    if (selector === '#education_level') {
                        populateClassGradeOptions(input.value, document.querySelector('#class_grade')?.value || '');
                        updateClassGradeLabel();
                        set('note.class_grade', document.querySelector('#class_grade')?.value || '');
                        populateSubjectOptions(
                            input.value,
                            document.querySelector('#class_grade')?.value || '',
                            document.querySelector('#subject')?.value || ''
                        );
                        set('note.subject', document.querySelector('#subject')?.value || '');
                    }

                    if (selector === '#class_grade') {
                        populateSubjectOptions(
                            document.querySelector('#education_level')?.value || '',
                            input.value,
                            document.querySelector('#subject')?.value || ''
                        );
                        set('note.subject', document.querySelector('#subject')?.value || '');
                    }
                });
            });

            document.querySelectorAll('input[name="access_type"]').forEach((input) => {
                input.addEventListener('change', () => set('note.access_type', input.value));
            });
        };

        const bindStepperNavigation = () => {
            if (!stepperItems.length) {
                return;
            }

            stepperItems.forEach((item) => {
                const url = item.dataset.noteStepUrl || '';

                item.addEventListener('click', () => navigateToStep(url));
                item.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        navigateToStep(url);
                    }
                });
            });

            window.setTimeout(scrollActiveStepperItemIntoView, 50);
            window.addEventListener('resize', scrollActiveStepperItemIntoView);
        };

        const bindStepTwo = () => {
            const list = document.querySelector('[data-note-chapter-list]');
            const emptyState = document.querySelector('[data-note-chapter-empty]');
            const addButton = document.querySelector('[data-note-add-chapter]');
            const modal = document.querySelector('[data-note-chapter-modal]');
            const form = modal?.querySelector('[data-note-chapter-form]');
            const titleInput = form?.querySelector('input[name="chapter_title"]');
            const pagesInput = form?.querySelector('input[name="chapter_pages"]');
            const contentInput = form?.querySelector('textarea[name="chapter_content"]');
            const indexInput = form?.querySelector('input[name="chapter_index"]');
            const sectionsList = form?.querySelector('[data-note-chapter-sections]');
            const addSectionButton = form?.querySelector('[data-note-add-section]');
            const closeButtons = modal ? Array.from(modal.querySelectorAll('[data-note-chapter-close]')) : [];
            const sectionModal = document.querySelector('[data-note-section-modal]');
            const sectionForm = sectionModal?.querySelector('[data-note-section-form]');
            const sectionIndexInput = sectionForm?.querySelector('input[name="section_index"]');
            const sectionChapterIndexInput = sectionForm?.querySelector('input[name="section_chapter_index"]');
            const sectionTitleInput = sectionForm?.querySelector('input[name="section_title"]');
            const sectionContentInput = sectionForm?.querySelector('textarea[name="section_content"]');
            const sectionPreviewBox = sectionForm?.querySelector('[data-note-section-preview]');
            const sectionCloseButtons = sectionModal ? Array.from(sectionModal.querySelectorAll('[data-note-section-close]')) : [];

            if (!list || !modal || !form || !titleInput || !pagesInput || !contentInput || !indexInput || !sectionsList || !addSectionButton || !sectionModal || !sectionForm || !sectionIndexInput || !sectionChapterIndexInput || !sectionTitleInput || !sectionContentInput || !sectionPreviewBox) {
                return;
            }

            let currentSectionIndex = null;
            let currentChapterIndex = null;
            let sectionEditorMode = 'draft';
            let chapterDraftSaveTimer = null;

            const collectSectionRows = () => Array.from(sectionsList.querySelectorAll('[data-note-section-row]')).map((row) => ({
                title: row.querySelector('[data-note-section-title]')?.value.trim() || '',
                content: row.querySelector('[data-note-section-content]')?.value || '<p></p>',
            }));

            const refreshSectionRowIndexes = () => {
                Array.from(sectionsList.querySelectorAll('[data-note-section-row]')).forEach((row, index) => {
                    row.dataset.noteSectionRow = String(index);
                    const titleInput = row.querySelector('[data-note-section-title]');
                    if (titleInput) {
                        titleInput.placeholder = `Section ${index + 1}`;
                    }
                });
            };

            const getChapterDraft = () => (
                state.chapter_draft && typeof state.chapter_draft === 'object'
                    ? state.chapter_draft
                    : null
            );

            const persistChapterDraft = () => {
                const title = titleInput.value.trim();
                const pages = Math.max(1, Number.parseInt(pagesInput.value, 10) || 1);
                const contentHtml = contentInput.value.trim();
                const sections = collectSectionRows();
                const hasMeaningfulData = Boolean(
                    title
                    || contentHtml
                    || sections.some((section) => section.title || stripHtml(section.content))
                );

                if (!hasMeaningfulData) {
                    state.chapter_draft = null;
                    writeState(state);
                    return;
                }

                state.chapter_draft = {
                    mode: indexInput.value === '' ? 'create' : 'edit',
                    chapter_index: indexInput.value === '' ? null : Number(indexInput.value),
                    insert_after_index: modal.dataset.insertAfter === '' ? null : Number(modal.dataset.insertAfter),
                    title,
                    pages,
                    content_html: contentHtml,
                    sections,
                };

                writeState(state);
            };

            const scheduleChapterDraftSave = () => {
                clearTimeout(chapterDraftSaveTimer);
                chapterDraftSaveTimer = window.setTimeout(persistChapterDraft, 450);
            };

            const syncSectionPreview = () => {
                sectionPreviewBox.innerHTML = sectionContentInput.value.trim() || '<p>No content yet.</p>';
            };

            const openSectionModalFromDraft = (sectionIndex) => {
                const row = sectionsList.querySelector(`[data-note-section-row="${sectionIndex}"]`);
                if (!row) {
                    return;
                }

                sectionEditorMode = 'draft';
                currentSectionIndex = sectionIndex;
                currentChapterIndex = Number(indexInput.value || 0);
                sectionIndexInput.value = String(sectionIndex);
                sectionChapterIndexInput.value = String(currentChapterIndex);
                sectionTitleInput.value = row.querySelector('[data-note-section-title]')?.value.trim() || '';
                sectionContentInput.value = row.querySelector('[data-note-section-content]')?.value || '<p></p>';
                syncSectionPreview();
                sectionModal.classList.add('is-open');
                sectionModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => sectionContentInput.focus(), 0);
            };

            const openSectionModalFromChapter = (chapterIndex, sectionIndex = null) => {
                const chapters = Array.isArray(state.curriculum) ? state.curriculum : [];
                const chapter = chapters[chapterIndex];
                const topics = Array.isArray(chapter?.children) ? chapter.children : [];
                const nextSectionIndex = Number.isInteger(sectionIndex) ? sectionIndex : topics.length;
                const topic = topics[nextSectionIndex] || null;

                if (!chapter) {
                    return;
                }

                sectionEditorMode = 'saved';
                currentSectionIndex = nextSectionIndex;
                currentChapterIndex = chapterIndex;
                sectionIndexInput.value = String(nextSectionIndex);
                sectionChapterIndexInput.value = String(chapterIndex);
                sectionTitleInput.value = String(topic?.title || '');
                sectionContentInput.value = topic ? extractReadingHtml(topic) : '<p></p>';
                syncSectionPreview();
                sectionModal.classList.add('is-open');
                sectionModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => sectionContentInput.focus(), 0);
            };

            const closeSectionModal = () => {
                sectionModal.classList.remove('is-open');
                sectionModal.setAttribute('aria-hidden', 'true');
                currentSectionIndex = null;
                sectionEditorMode = 'draft';
                sectionIndexInput.value = '';
                sectionChapterIndexInput.value = '';
                sectionTitleInput.value = '';
                sectionContentInput.value = '<p></p>';
                syncSectionPreview();
            };

            const renderSectionRows = (sections = []) => {
                sectionsList.innerHTML = '';

                if (!sections.length) {
                    const empty = document.createElement('div');
                    empty.className = 'note-chapter-sections__empty';
                    empty.textContent = 'No optional sections yet. Add one only if this chapter needs a breakdown.';
                    sectionsList.appendChild(empty);
                    return;
                }

                sections.forEach((section, sectionIndex) => {
                    const row = document.createElement('div');
                    row.className = 'note-chapter-section-row';
                    row.dataset.noteSectionRow = String(sectionIndex);
                    row.setAttribute('draggable', 'true');

                    const handle = document.createElement('button');
                    handle.type = 'button';
                    handle.className = 'note-chapter-section-row__handle';
                    handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
                    handle.setAttribute('aria-label', 'Drag section to change position');

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.setAttribute('data-note-section-title', 'true');
                    input.placeholder = `Section ${sectionIndex + 1}`;
                    input.value = String(section.title || '');

                    const content = document.createElement('input');
                    content.type = 'hidden';
                    content.setAttribute('data-note-section-content', 'true');
                    content.value = String(section.content || '<p></p>');

                    const contentButton = document.createElement('button');
                    contentButton.type = 'button';
                    contentButton.className = 'note-chapter-section-row__content';
                    contentButton.innerHTML = '<i class="fas fa-pen-nib"></i>';
                    contentButton.setAttribute('aria-label', 'Edit reading content');

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.setAttribute('data-note-section-remove', 'true');
                    removeButton.setAttribute('aria-label', 'Remove section');
                    removeButton.innerHTML = '<i class="fas fa-trash-alt"></i>';

                    contentButton.addEventListener('click', () => openSectionModalFromDraft(sectionIndex));

                    removeButton.addEventListener('click', () => {
                        const nextSections = collectSectionRows().filter((_, currentIndex) => currentIndex !== sectionIndex);
                        renderSectionRows(nextSections);
                        scheduleChapterDraftSave();
                    });

                    row.addEventListener('dragstart', (event) => {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', String(sectionIndex));
                        row.classList.add('is-dragging');
                    });

                    row.addEventListener('dragend', () => {
                        row.classList.remove('is-dragging');
                    });

                    row.addEventListener('dragover', (event) => {
                        event.preventDefault();
                    });

                    row.addEventListener('drop', (event) => {
                        event.preventDefault();
                        const draggedIndex = Number(event.dataTransfer.getData('text/plain'));
                        const targetIndex = sectionIndex;

                        if (Number.isNaN(draggedIndex) || draggedIndex === targetIndex) {
                            return;
                        }

                        const currentSections = collectSectionRows();
                        const [moved] = currentSections.splice(draggedIndex, 1);
                        currentSections.splice(targetIndex, 0, moved);
                        renderSectionRows(currentSections);
                        scheduleChapterDraftSave();
                    });

                    row.appendChild(handle);
                    row.appendChild(input);
                    row.appendChild(content);
                    row.appendChild(contentButton);
                    row.appendChild(removeButton);
                    sectionsList.appendChild(row);
                });

                refreshSectionRowIndexes();
            };

            const openModal = (chapterIndex = null, insertAfterIndex = null) => {
                const chapters = Array.isArray(state.curriculum) ? state.curriculum : [];
                const chapter = chapterIndex !== null ? chapters[chapterIndex] : null;
                const draft = getChapterDraft();
                const draftMatchesChapter = chapterIndex !== null
                    && draft
                    && draft.mode === 'edit'
                    && Number(draft.chapter_index) === chapterIndex;
                const draftForCreate = chapterIndex === null && draft && draft.mode === 'create' ? draft : null;
                const draftSource = draftMatchesChapter ? draft : draftForCreate;
                const sections = Array.isArray(draftSource?.sections)
                    ? draftSource.sections
                    : (chapter ? sectionRowsFromChapter(chapter) : []);

                indexInput.value = chapterIndex !== null ? String(chapterIndex) : '';
                modal.dataset.insertAfter = String(insertAfterIndex ?? draftSource?.insert_after_index ?? '');
                titleInput.value = draftSource?.title ?? chapter?.title ?? '';
                pagesInput.value = draftSource?.pages ?? chapter?.pages ?? 1;
                contentInput.value = draftSource?.content_html ?? chapter?.content_html ?? '';
                renderSectionRows(sections);
                modal.dataset.mode = chapterIndex !== null ? 'edit' : 'create';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                setTimeout(() => titleInput.focus(), 0);
            };

            const closeModal = (preserveDraft = true) => {
                if (preserveDraft) {
                    persistChapterDraft();
                }

                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                form.reset();
                indexInput.value = '';
                modal.dataset.insertAfter = '';
                pagesInput.value = '1';
                contentInput.value = '';
                renderSectionRows([]);
                clearTimeout(chapterDraftSaveTimer);
            };

            const render = () => {
                const chapters = Array.isArray(state.curriculum) ? state.curriculum.map(normalizeChapter) : [];
                state.curriculum = chapters;
                list.innerHTML = '';

                if (emptyState) {
                    emptyState.style.display = chapters.length ? 'none' : 'grid';
                }

                chapters.forEach((chapter, index) => {
                    const row = document.createElement('article');
                    row.className = 'note-chapter-row';
                    row.dataset.noteChapterRow = 'true';
                    row.dataset.noteChapterIndex = String(index);
                    row.dataset.noteChapterTitle = chapter.title;
                    row.dataset.noteChapterPages = String(chapter.pages);
                    row.dataset.noteTopicsExpanded = chapter.ui_topics_expanded ? 'true' : 'false';
                    row.setAttribute('draggable', 'true');
                    row.innerHTML = `
                        <div class="note-chapter-row__handle" aria-hidden="true">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="note-chapter-row__title">
                            <div class="note-chapter-row__title-line">
                                <span class="note-chapter-row__number">Chapter ${index + 1}</span>
                                <strong></strong>
                            </div>
                        </div>
                        <div class="note-chapter-row__pages"></div>
                        <div class="note-chapter-row__actions">
                            <button type="button" data-note-chapter-edit aria-label="Edit chapter">
                                <i class="far fa-edit"></i>
                            </button>
                            <button type="button" data-note-chapter-delete aria-label="Delete chapter">
                                <i class="far fa-trash-alt"></i>
                            </button>
                        </div>
                        <div class="note-chapter-topic-list">
                            <div class="note-chapter-row__section-count"></div>
                        </div>
                        <div class="note-chapter-row__footer">
                            <button type="button" class="note-chapter-row__add" data-note-chapter-add-after>
                                <i class="fas fa-plus"></i>
                                <span>Add Chapter</span>
                            </button>
                        </div>
                    `;
                    row.querySelector('.note-chapter-row__title strong').textContent = chapter.title;
                    row.querySelector('.note-chapter-row__pages').textContent = `${chapter.pages} ${chapter.pages === 1 ? 'Page' : 'Pages'}`;
                    const topicCount = Array.isArray(chapter.children) ? chapter.children.length : 0;
                    const sectionCountEl = row.querySelector('.note-chapter-row__section-count');
                    if (topicCount > 0 && sectionCountEl) {
                        sectionCountEl.textContent = `${topicCount} ${topicCount === 1 ? 'section' : 'sections'}`;
                        sectionCountEl.classList.add('is-visible');
                    }
                    row.querySelector('.note-chapter-row__title').insertAdjacentHTML(
                        'beforeend',
                        `<span class="note-chapter-row__content-preview"><strong>Main content:</strong> ${previewText(chapter.content_html)}</span>`
                    );

                    const topicList = row.querySelector('.note-chapter-topic-list');
                    const topics = Array.isArray(chapter.children) ? chapter.children : [];
                    const isExpanded = chapter.ui_topics_expanded === true;
                    const visibleTopics = isExpanded ? topics : topics.slice(0, 2);

                    if (topics.length) {
                        visibleTopics.forEach((topic, topicIndex) => {
                            const topicRow = document.createElement('div');
                            topicRow.className = 'note-chapter-topic-item';
                            const html = extractReadingHtml(topic);
                            topicRow.innerHTML = `
                                <div class="note-chapter-topic-item__body">
                                    <strong>${String(topic.title || '')}</strong>
                                    <small>${html.replace(/<[^>]*>/g, '').trim().slice(0, 120) || 'No content yet.'}</small>
                                </div>
                                <button type="button" data-note-topic-edit aria-label="Edit reading content">
                                    <i class="fas fa-pen-nib"></i>
                                </button>
                            `;
                            topicRow.querySelector('[data-note-topic-edit]')?.addEventListener('click', () => {
                                openSectionModalFromChapter(index, topicIndex);
                            });
                            topicList.appendChild(topicRow);
                        });

                        if (topics.length > 2) {
                            const moreRow = document.createElement('button');
                            moreRow.type = 'button';
                            moreRow.className = 'note-chapter-topic-more';
                            moreRow.textContent = isExpanded
                                ? `Show fewer topics`
                                : `+ ${topics.length - 2} more ${topics.length - 2 === 1 ? 'topic' : 'topics'}`;
                            moreRow.addEventListener('click', () => {
                                const chaptersCopy = [...chapters];
                                chaptersCopy[index] = normalizeChapter({
                                    ...chaptersCopy[index],
                                    ui_topics_expanded: !isExpanded,
                                });
                                updateCurriculum(chaptersCopy);
                                render();
                            });
                            topicList.appendChild(moreRow);
                        }
                    } else {
                        const emptyTopic = document.createElement('div');
                        emptyTopic.className = 'note-chapter-topic-empty';
                        emptyTopic.innerHTML = `
                            <span>No sections added yet.</span>
                            <button type="button" class="note-chapter-topic-empty__action" data-note-topic-add>
                                <i class="fas fa-plus"></i>
                                <span>Add Topic</span>
                            </button>
                        `;
                        emptyTopic.querySelector('[data-note-topic-add]')?.addEventListener('click', () => {
                            openSectionModalFromChapter(index);
                        });
                        topicList.appendChild(emptyTopic);
                    }

                    row.addEventListener('dragstart', (event) => {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', String(index));
                        row.classList.add('is-dragging');
                    });

                    row.addEventListener('dragend', () => {
                        row.classList.remove('is-dragging');
                    });

                    row.addEventListener('dragover', (event) => {
                        event.preventDefault();
                    });

                    row.addEventListener('drop', (event) => {
                        event.preventDefault();
                        const draggedIndex = Number(event.dataTransfer.getData('text/plain'));
                        const targetIndex = index;

                        if (Number.isNaN(draggedIndex) || draggedIndex === targetIndex) {
                            return;
                        }

                        const chaptersCopy = [...chapters];
                        const [moved] = chaptersCopy.splice(draggedIndex, 1);
                        chaptersCopy.splice(targetIndex, 0, moved);
                        updateCurriculum(chaptersCopy);
                        render();
                    });

                    row.querySelector('[data-note-chapter-edit]')?.addEventListener('click', () => openModal(index));
                    row.querySelector('[data-note-chapter-delete]')?.addEventListener('click', () => {
                        const chaptersCopy = [...chapters];
                        chaptersCopy.splice(index, 1);
                        updateCurriculum(chaptersCopy);
                        render();
                    });
                    row.querySelector('[data-note-chapter-add-after]')?.addEventListener('click', () => openModal(null, index));

                    list.appendChild(row);
                });
            };

            addButton?.addEventListener('click', () => openModal());
            document.querySelector('[data-note-add-chapter-empty]')?.addEventListener('click', () => openModal());
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));
            sectionCloseButtons.forEach((button) => button.addEventListener('click', closeSectionModal));

            titleInput.addEventListener('input', scheduleChapterDraftSave);
            pagesInput.addEventListener('input', scheduleChapterDraftSave);
            contentInput.addEventListener('input', scheduleChapterDraftSave);
            sectionsList.addEventListener('input', (event) => {
                if (event.target?.matches?.('[data-note-section-title]')) {
                    scheduleChapterDraftSave();
                }
            });

            window.addEventListener('beforeunload', () => {
                if (modal.classList.contains('is-open')) {
                    persistChapterDraft();
                }
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();

                const chapterTitle = titleInput.value.trim();
                const chapterPages = Math.max(1, Number.parseInt(pagesInput.value, 10) || 1);
                const chapterContent = contentInput.value.trim();

                if (!chapterTitle) {
                    titleInput.focus();
                    return;
                }

                const sectionRows = collectSectionRows();
                const chapterChildren = sectionRows.map((section) => createTopicStub(chapterTitle, section.title || null, section.content || '<p></p>'));

                const chapters = Array.isArray(state.curriculum) ? [...state.curriculum] : [];
                const chapterIndex = indexInput.value === '' ? null : Number(indexInput.value);
                const existing = chapterIndex !== null && Number.isInteger(chapterIndex) ? chapters[chapterIndex] : null;
                const chapter = normalizeChapter({
                    ...(existing || {}),
                    title: chapterTitle,
                    pages: chapterPages,
                    content_html: chapterContent,
                    children: chapterChildren,
                });

                if (chapterIndex === null || ! Number.isInteger(chapterIndex)) {
                    const insertAfterIndex = Number.parseInt(modal.dataset.insertAfter || '', 10);
                    if (Number.isInteger(insertAfterIndex)) {
                        chapters.splice(insertAfterIndex + 1, 0, chapter);
                    } else {
                        chapters.push(chapter);
                    }
                } else {
                    chapters[chapterIndex] = chapter;
                }

                updateCurriculum(chapters);
                state.chapter_draft = null;
                writeState(state);
                render();
                closeModal(false);
            });

            addSectionButton.addEventListener('click', () => {
                const currentSections = collectSectionRows();
                renderSectionRows([...currentSections, { title: '', content: '<p></p>' }]);
                openSectionModalFromDraft(currentSections.length);
                scheduleChapterDraftSave();
            });

            pagesInput.addEventListener('blur', () => {
                if (!pagesInput.value.trim()) {
                    pagesInput.value = '1';
                }
            });

            sectionContentInput.addEventListener('input', syncSectionPreview);

            sectionForm.addEventListener('submit', (event) => {
                event.preventDefault();

                const sectionTitle = sectionTitleInput.value.trim();
                const sectionContent = sectionContentInput.value.trim() || '<p></p>';
                const sectionIndex = Number(sectionIndexInput.value);

                if (! Number.isInteger(sectionIndex)) {
                    return;
                }

                const currentSections = collectSectionRows();
                if (sectionEditorMode === 'saved') {
                    const chapters = Array.isArray(state.curriculum) ? [...state.curriculum] : [];
                    const chapterIndex = Number(sectionChapterIndexInput.value);
                    const chapter = Number.isInteger(chapterIndex) ? chapters[chapterIndex] : null;

                    if (!chapter) {
                        return;
                    }

                    const nextChildren = Array.isArray(chapter.children) ? [...chapter.children] : [];
                    nextChildren[sectionIndex] = createTopicStub(
                        chapter.title || 'Chapter',
                        sectionTitle || nextChildren[sectionIndex]?.title || `Section ${sectionIndex + 1}`,
                        sectionContent
                    );

                    chapters[chapterIndex] = normalizeChapter({
                        ...chapter,
                        children: nextChildren,
                    });
                    updateCurriculum(chapters);
                    render();
                } else {
                    currentSections[sectionIndex] = {
                        title: sectionTitle || currentSections[sectionIndex]?.title || `Section ${sectionIndex + 1}`,
                        content: sectionContent,
                    };

                    renderSectionRows(currentSections);
                    scheduleChapterDraftSave();
                }
                closeSectionModal();
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            sectionModal.addEventListener('click', (event) => {
                if (event.target === sectionModal) {
                    closeSectionModal();
                }
            });

            render();
        };

        const bindStepThree = () => {
            const input = document.querySelector('#note-files');
            const list = document.querySelector('[data-note-file-list]');
            const emptyState = document.querySelector('[data-note-file-empty]');
            const countLabel = document.querySelector('[data-note-file-count]');
            const sizeLabel = document.querySelector('[data-note-file-size]');
            const uploadStatus = document.querySelector('[data-note-upload-status]');
            const uploadErrors = document.querySelector('[data-note-upload-errors]');
            const summaryLabels = {
                uploaded: document.querySelector('[data-note-upload-summary-uploaded]'),
                pending: document.querySelector('[data-note-upload-summary-pending]'),
                failed: document.querySelector('[data-note-upload-summary-failed]'),
                size: document.querySelector('[data-note-upload-summary-size]'),
            };
            const dropzone = document.querySelector('.note-dropzone');
            const uploadUrl = root.dataset.noteAttachmentsUploadUrl || '';
            const deleteUrl = root.dataset.noteAttachmentsDeleteUrl || '';

            if (!input || !list || !uploadUrl) {
                return;
            }

            let isUploading = false;
            const pendingUploads = [];
            let pendingId = 0;
            let draggedResourceIndex = null;
            const uploadRequests = new Map();

            const setStatus = (message = '', tone = '') => {
                if (!uploadStatus) {
                    return;
                }

                uploadStatus.textContent = message;
                uploadStatus.className = `note-upload-status${tone ? ` is-${tone}` : ''}`;
                uploadStatus.style.display = message ? 'block' : 'none';
            };

            const setErrors = (messages = []) => {
                if (!uploadErrors) {
                    return;
                }

                uploadErrors.innerHTML = '';

                messages.forEach((message) => {
                    const error = document.createElement('div');
                    error.className = 'note-upload-error';
                    error.innerHTML = `<i class="fas fa-circle-exclamation"></i><span>${message}</span>`;
                    uploadErrors.appendChild(error);
                });
            };

            const setBusy = (busy) => {
                isUploading = busy;
                input.disabled = busy;

                if (dropzone) {
                    dropzone.classList.toggle('is-loading', busy);
                }
            };

            const normalizeResource = (file = {}) => {
                const sizeBytes = Number.isFinite(file.size_bytes)
                    ? file.size_bytes
                    : parseSizeToBytes(file.size || 0);

                return {
                    ...file,
                    title: String(file.title || file.name || file.url_or_path || 'Attachment').trim(),
                    resource_type: file.resource_type || 'file',
                    url_or_path: String(file.url_or_path || '').trim(),
                    download_url: file.download_url || '',
                    size: file.size || (sizeBytes > 0 ? formatBytes(sizeBytes) : ''),
                    size_bytes: sizeBytes,
                };
            };

            const persistResources = (resources) => {
                state.resources = resources.map((resource) => normalizeResource(resource));
                writeState(state);
                render();
            };

            const downloadResource = (resource) => {
                const url = resolveDownloadUrl(resource);

                if (!url || url === '#') {
                    return;
                }

                window.open(url, '_blank', 'noopener,noreferrer');
            };

            const removeResource = async (index) => {
                const resources = Array.isArray(state.resources) ? [...state.resources] : [];
                const resource = resources[index];

                if (!resource) {
                    return;
                }

                if (!window.confirm(`Remove ${resource.title || 'this file'}?`)) {
                    return;
                }

                resources.splice(index, 1);
                persistResources(resources);
                setStatus('');
                setErrors([]);

                if (deleteUrl && resource.url_or_path) {
                    try {
                        const response = await fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                path: resource.url_or_path,
                            }),
                        });

                        if (!response.ok) {
                            throw new Error('Failed to delete uploaded file.');
                        }
                    } catch (error) {
                        resources.splice(index, 0, resource);
                        persistResources(resources);
                        setStatus('Could not remove file from the server.', 'error');
                    }
                }
            };

            const discardPendingUpload = (id) => {
                const index = pendingUploads.findIndex((entry) => entry.id === id);

                if (index >= 0) {
                    pendingUploads.splice(index, 1);
                    render();
                }
            };

            const clearPendingUploadClasses = () => {
                list.querySelectorAll('.note-file-row.is-dragging, .note-file-row.is-drop-target').forEach((row) => {
                    row.classList.remove('is-dragging', 'is-drop-target');
                });
            };

            const cancelPendingUpload = (id) => {
                const entry = pendingUploads.find((item) => item.id === id);

                if (!entry) {
                    return;
                }

                if (entry.state === 'uploading') {
                    entry.cancelRequested = true;
                    const request = uploadRequests.get(id);

                    if (request) {
                        request.abort();
                        return;
                    }
                }

                discardPendingUpload(id);
                updateSummary();
                setStatus('Upload canceled.', 'error');
            };

            const updatePendingUpload = (id, patch = {}) => {
                const item = pendingUploads.find((entry) => entry.id === id);

                if (!item) {
                    return;
                }

                Object.assign(item, patch);
                render();
            };

            const uploadSingleFile = (entry, onProgress) => new Promise((resolve, reject) => {
                const file = entry.file;
                const formData = new FormData();
                formData.append('files[]', file);

                const xhr = new XMLHttpRequest();
                uploadRequests.set(entry.id, xhr);
                xhr.open('POST', uploadUrl, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');

                const cleanup = () => {
                    uploadRequests.delete(entry.id);
                };

                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable && typeof onProgress === 'function') {
                        onProgress(Math.min(100, Math.round((event.loaded / event.total) * 100)));
                    }
                });

                xhr.onload = () => {
                    let payload = {};

                    try {
                        payload = JSON.parse(xhr.responseText || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    if (xhr.status < 200 || xhr.status >= 300) {
                        const firstError = payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : null;
                        cleanup();
                        reject(new Error(payload?.message || firstError || 'Unable to upload files.'));
                        return;
                    }

                    const uploaded = Array.isArray(payload.files) ? payload.files : [];

                    if (!uploaded.length) {
                        cleanup();
                        reject(new Error('No files were uploaded.'));
                        return;
                    }

                    cleanup();
                    resolve(uploaded[0]);
                };

                xhr.onerror = () => {
                    cleanup();
                    reject(new Error('Upload failed.'));
                };
                xhr.onabort = () => {
                    cleanup();
                    const error = new Error('Upload canceled.');
                    error.name = 'AbortError';
                    reject(error);
                };
                xhr.send(formData);
            });

            const updateSummary = () => {
                const uploadedResources = Array.isArray(state.resources) ? state.resources.map((file) => normalizeResource(file)) : [];
                const activeUploads = pendingUploads.filter((entry) => entry.state === 'queued' || entry.state === 'uploading');
                const failedUploads = pendingUploads.filter((entry) => entry.state === 'failed');
                const summary = calculateResourceSummary([
                    ...uploadedResources,
                    ...pendingUploads.map((entry) => ({
                        size_bytes: entry.size_bytes,
                    })),
                ]);

                if (summaryLabels.uploaded) summaryLabels.uploaded.textContent = String(uploadedResources.length);
                if (summaryLabels.pending) summaryLabels.pending.textContent = String(activeUploads.length);
                if (summaryLabels.failed) summaryLabels.failed.textContent = String(failedUploads.length);
                if (summaryLabels.size) summaryLabels.size.textContent = summary.totalSize;
                if (countLabel) countLabel.textContent = String(uploadedResources.length);
                if (sizeLabel) sizeLabel.textContent = calculateResourceSummary(uploadedResources).totalSize;
            };

            const persistUploadedResource = (uploaded) => {
                const existing = Array.isArray(state.resources) ? [...state.resources] : [];
                const resource = normalizeResource(uploaded);
                const existingIndex = existing.findIndex((item) => item.url_or_path && item.url_or_path === resource.url_or_path);

                if (existingIndex >= 0) {
                    existing[existingIndex] = resource;
                } else {
                    existing.push(resource);
                }

                persistResources(existing);
            };

            const uploadPendingEntry = async (entry) => {
                if (!entry) {
                    return false;
                }

                entry.state = 'uploading';
                entry.error = '';
                entry.progress = 0;
                entry.cancelRequested = false;
                render();
                setStatus(`Uploading ${entry.name}...`);

                try {
                    const uploaded = await uploadSingleFile(entry, (progress) => {
                        updatePendingUpload(entry.id, {
                            progress,
                        });
                    });

                    discardPendingUpload(entry.id);
                    persistUploadedResource(uploaded);
                    setStatus(`Uploaded ${entry.name}.`, 'success');
                    return true;
                } catch (error) {
                    if (error?.name === 'AbortError' || entry.cancelRequested) {
                        discardPendingUpload(entry.id);
                        setStatus('Upload canceled.', 'error');
                        return false;
                    }

                    entry.state = 'failed';
                    entry.error = error?.message || 'Upload failed.';
                    entry.progress = 0;
                    setStatus(entry.error, 'error');
                    render();
                    return false;
                }
            };

            const processQueue = async () => {
                if (isUploading) {
                    return;
                }

                const queue = pendingUploads.filter((entry) => entry.state === 'queued');
                let hadInterruptedUpload = false;

                if (!queue.length) {
                    updateSummary();
                    render();
                    return;
                }

                setBusy(true);

                try {
                    for (const entry of queue) {
                        // Keep moving through the batch even if one file fails.
                        const completed = await uploadPendingEntry(entry);

                        if (!completed) {
                            hadInterruptedUpload = true;
                        }
                    }
                } finally {
                    setBusy(false);
                    input.value = '';
                    updateSummary();
                    render();

                    const failedCount = pendingUploads.filter((entry) => entry.state === 'failed').length;
                    const doneCount = Array.isArray(state.resources) ? state.resources.length : 0;

                    if (failedCount > 0 || hadInterruptedUpload) {
                        setStatus(doneCount > 0
                            ? `${doneCount} uploaded, ${failedCount} failed.`
                            : `${Math.max(failedCount, 1)} file${Math.max(failedCount, 1) === 1 ? '' : 's'} need attention.`,
                            'error');
                    } else if (queue.length > 0) {
                        setStatus(`Uploaded ${queue.length} file${queue.length === 1 ? '' : 's'} successfully.`, 'success');
                    }
                }
            };

            const retryPendingUpload = async (id) => {
                if (isUploading) {
                    return;
                }

                const entry = pendingUploads.find((item) => item.id === id);

                if (!entry) {
                    return;
                }

                entry.state = 'queued';
                entry.error = '';
                entry.progress = 0;
                await uploadPendingEntry(entry);
                updateSummary();
                render();
            };

            const queueFiles = (files = []) => {
                const selectedFiles = files.filter(Boolean);

                if (!selectedFiles.length || isUploading) {
                    return;
                }

                setErrors([]);

                const validationErrors = [];
                const validFiles = [];

                selectedFiles.forEach((file) => {
                    const validation = validateAttachmentFile(file);

                    if (!validation.valid) {
                        validationErrors.push(validation.message);
                        return;
                    }

                    validFiles.push(file);
                });

                if (validationErrors.length) {
                    setErrors(validationErrors);
                    setStatus('Some files were skipped because they are not allowed.', 'error');
                }

                if (!validFiles.length) {
                    input.value = '';
                    return;
                }

                validFiles.forEach((file) => {
                    pendingUploads.push({
                        id: `pending-${Date.now()}-${pendingId += 1}`,
                        file,
                        name: file.name,
                        size: formatBytes(file.size),
                        size_bytes: file.size,
                        type_label: attachmentTypeFromFile(file),
                        progress: 0,
                        state: 'queued',
                        error: '',
                    });
                });

                updateSummary();
                render();
                processQueue();
            };

            const render = () => {
                list.innerHTML = '';
                const uploadedResources = Array.isArray(state.resources) ? state.resources.map((file) => normalizeResource(file)) : [];
                state.resources = uploadedResources;
                const totalVisibleRows = uploadedResources.length + pendingUploads.length;

                updateSummary();

                if (emptyState) {
                    emptyState.style.display = totalVisibleRows ? 'none' : 'grid';
                }

                if (!totalVisibleRows) {
                    return;
                }

                uploadedResources.forEach((file, index) => {
                    const row = document.createElement('article');
                    row.className = 'note-file-row';
                    row.draggable = true;
                    row.dataset.noteResourceIndex = String(index);
                    row.innerHTML = `
                        <div class="note-file-row__icon"><i class="${fileIconClass(file.title || file.url_or_path || '')}"></i></div>
                        <div class="note-file-row__body">
                            <strong>${file.title || ''}</strong>
                            <span>${file.size || ''}</span>
                        </div>
                        <div class="note-file-row__actions">
                            <div class="note-file-row__meta">
                                <button type="button" class="note-file-row__drag" aria-label="Drag to reorder"><i class="fas fa-grip-vertical"></i></button>
                                <span class="note-file-row__type">${attachmentTypeLabel(file)}</span>
                                <span class="note-file-row__status">
                                    <span class="note-file-row__status-badge">Uploaded</span>
                                </span>
                            </div>
                            <div class="note-file-row__meta">
                                <button type="button" data-note-download aria-label="Download file"><i class="fas fa-download"></i></button>
                                <button type="button" data-note-remove aria-label="Remove file" class="is-danger"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                    `;

                    row.addEventListener('dragstart', (event) => {
                        draggedResourceIndex = index;
                        row.classList.add('is-dragging');
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', String(index));
                    });

                    row.addEventListener('dragend', () => {
                        draggedResourceIndex = null;
                        clearPendingUploadClasses();
                    });

                    row.addEventListener('dragover', (event) => {
                        if (draggedResourceIndex === null || draggedResourceIndex === index) {
                            return;
                        }

                        event.preventDefault();
                        row.classList.add('is-drop-target');
                        event.dataTransfer.dropEffect = 'move';
                    });

                    row.addEventListener('dragleave', () => {
                        row.classList.remove('is-drop-target');
                    });

                    row.addEventListener('drop', (event) => {
                        event.preventDefault();
                        const sourceIndex = draggedResourceIndex ?? Number(event.dataTransfer.getData('text/plain'));
                        const targetIndex = index;

                        if (Number.isNaN(sourceIndex) || sourceIndex === targetIndex) {
                            draggedResourceIndex = null;
                            clearPendingUploadClasses();
                            return;
                        }

                        const resources = Array.isArray(state.resources) ? [...state.resources] : [];
                        const [moved] = resources.splice(sourceIndex, 1);
                        resources.splice(targetIndex, 0, moved);
                        persistResources(resources);
                        draggedResourceIndex = null;
                        clearPendingUploadClasses();
                    });

                    row.querySelector('[data-note-download]')?.addEventListener('click', () => downloadResource(file));
                    row.querySelector('[data-note-remove]')?.addEventListener('click', () => removeResource(index));
                    list.appendChild(row);
                });

                pendingUploads.forEach((file) => {
                    const row = document.createElement('article');
                    const isFailed = file.state === 'failed';
                    const isUploading = file.state === 'uploading';
                    const statusTone = isFailed ? 'error' : (isUploading ? 'uploading' : 'uploading');
                    const statusLabel = isFailed
                        ? (file.error || 'Upload failed')
                        : isUploading
                            ? `${file.progress || 0}%`
                            : 'Queued';

                    row.className = `note-file-row${isUploading ? ' is-uploading' : ''}${isFailed ? ' is-upload-failed' : ''}`;
                    row.innerHTML = `
                        <div class="note-file-row__icon"><i class="${fileIconClass(file.name || '')}"></i></div>
                        <div class="note-file-row__body">
                            <strong>${file.name || ''}</strong>
                            <span>${file.size || ''}</span>
                            <div class="note-file-row__progress" aria-hidden="true">
                                <div class="note-file-row__progress-bar" style="width: ${isFailed ? 0 : (file.progress || 0)}%"></div>
                            </div>
                        </div>
                        <div class="note-file-row__actions">
                            <div class="note-file-row__meta">
                                <span class="note-file-row__type">${file.type_label || 'FILE'}</span>
                                <span class="note-file-row__status">
                                    <span class="note-file-row__status-badge note-file-row__status-badge--${statusTone}">${statusLabel}</span>
                                </span>
                            </div>
                            <div class="note-file-row__meta">
                                ${isFailed ? '<button type="button" data-note-retry class="is-primary" aria-label="Retry upload"><i class="fas fa-rotate-right"></i></button>' : ''}
                                <button type="button" data-note-cancel class="is-danger" aria-label="Cancel upload"><i class="fas fa-xmark"></i></button>
                            </div>
                        </div>
                    `;

                    row.querySelector('[data-note-retry]')?.addEventListener('click', () => retryPendingUpload(file.id));
                    row.querySelector('[data-note-cancel]')?.addEventListener('click', () => cancelPendingUpload(file.id));
                    list.appendChild(row);
                });
            };

            input.addEventListener('change', () => {
                queueFiles(Array.from(input.files || []));
            });

            if (dropzone) {
                dropzone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });

                dropzone.addEventListener('dragleave', () => {
                    dropzone.classList.remove('is-dragover');
                });

                dropzone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                    queueFiles(Array.from(event.dataTransfer?.files || []));
                });
            }

            updateSummary();
            render();
        };

        const bindStepFour = () => {
            const switches = Array.from(root.querySelectorAll('.note-switch input'));
            const visibilityPublic = root.querySelector('input[name="visibility"][value="public"]');
            const visibilityPrivate = root.querySelector('input[name="visibility"][value="private"]');

            if (switches[0]) {
                switches[0].checked = Boolean(get('settings.allow_download', true));
                switches[0].addEventListener('change', () => set('settings.allow_download', switches[0].checked));
            }
            if (switches[1]) {
                switches[1].checked = Boolean(get('settings.add_to_bundle', true));
                switches[1].addEventListener('change', () => set('settings.add_to_bundle', switches[1].checked));
            }
            if (switches[2]) {
                switches[2].checked = Boolean(get('settings.featured_note', true));
                switches[2].addEventListener('change', () => set('settings.featured_note', switches[2].checked));
            }
            if (switches[3]) {
                switches[3].checked = Boolean(get('settings.allow_comments', true));
                switches[3].addEventListener('change', () => set('settings.allow_comments', switches[3].checked));
            }

            if (visibilityPublic) {
                visibilityPublic.checked = get('settings.visibility', 'public') === 'public';
                visibilityPublic.addEventListener('change', () => set('settings.visibility', 'public'));
            }

            if (visibilityPrivate) {
                visibilityPrivate.checked = get('settings.visibility', 'public') === 'private';
                visibilityPrivate.addEventListener('change', () => set('settings.visibility', 'private'));
            }

            [
                ['#meta_title', 'settings.meta_title'],
                ['#meta_description', 'settings.meta_description'],
                ['#keywords', 'settings.keywords'],
                ['#sort_order', 'settings.sort_order'],
            ].forEach(([selector, path]) => {
                const input = document.querySelector(selector);
                if (!input) {
                    return;
                }

                input.addEventListener('input', () => set(path, input.value));
            });
        };

        bindStepperNavigation();

        const bindStepFive = () => {
            const form = root.querySelector('[data-note-publish-form]');
            const previewSelect = document.querySelector('[data-note-preview-pages]');
            const fileList = document.querySelector('[data-note-review-file-list]');
            const emptyState = document.querySelector('[data-note-review-file-empty]');
            const countLabel = document.querySelector('[data-note-review-file-count]');
            const sizeLabel = document.querySelector('[data-note-review-file-size]');
            const summaryLabels = {
                chapters: document.querySelector('[data-note-summary-chapters]'),
                pages: document.querySelector('[data-note-summary-pages]'),
                words: document.querySelector('[data-note-summary-words]'),
                examples: document.querySelector('[data-note-summary-examples]'),
                pastQuestions: document.querySelector('[data-note-summary-past-questions]'),
                formulas: document.querySelector('[data-note-summary-formulas]'),
                diagrams: document.querySelector('[data-note-summary-diagrams]'),
            };

            if (!form) {
                return;
            }

            const payloadInput = form.querySelector('input[name="note_payload"]');
            const hiddenFields = {
                title: form.querySelector('input[name="title"]'),
                description: form.querySelector('textarea[name="description"]'),
                price: form.querySelector('input[name="price"]'),
                status: form.querySelector('input[name="status"]'),
            };

            const fillFromState = () => {
                if (hiddenFields.title) hiddenFields.title.value = get('note.title', '');
                if (hiddenFields.description) hiddenFields.description.value = get('note.description', '');
                if (hiddenFields.price) hiddenFields.price.value = get('note.price', '');
                syncNotePriceDisplays(get('note.price', ''));
                if (hiddenFields.status) hiddenFields.status.value = 'active';
                if (previewSelect) previewSelect.value = get('note.preview_pages', 'No preview');
                const resources = Array.isArray(get('resources', [])) ? get('resources', []) : [];
                const summary = calculateResourceSummary(resources);
                const curriculumSummary = calculateCurriculumSummary(Array.isArray(get('curriculum', [])) ? get('curriculum', []) : []);

                if (countLabel) countLabel.textContent = String(summary.count);
                if (sizeLabel) sizeLabel.textContent = summary.totalSize;
                if (summaryLabels.chapters) summaryLabels.chapters.textContent = String(curriculumSummary.chapters);
                if (summaryLabels.pages) summaryLabels.pages.textContent = `${curriculumSummary.pages} Pages`;
                if (summaryLabels.words) summaryLabels.words.textContent = `${curriculumSummary.words} words`;
                if (summaryLabels.examples) summaryLabels.examples.textContent = curriculumSummary.includes_examples ? 'Yes' : 'No';
                if (summaryLabels.pastQuestions) summaryLabels.pastQuestions.textContent = curriculumSummary.includes_past_questions ? 'Yes' : 'No';
                if (summaryLabels.formulas) summaryLabels.formulas.textContent = curriculumSummary.includes_formulas ? 'Yes' : 'No';
                if (summaryLabels.diagrams) summaryLabels.diagrams.textContent = curriculumSummary.includes_diagrams ? 'Yes' : 'No';

                if (fileList) {
                    fileList.innerHTML = '';
                    resources.forEach((file) => {
                        const row = document.createElement('div');
                        row.className = 'note-review-file';
                        row.innerHTML = `
                            <div class="note-review-file__icon"><i class="${fileIconClass(file.title || file.url_or_path || '')}"></i></div>
                            <div class="note-review-file__name">${file.title || ''}</div>
                            <div class="note-review-file__size">${file.size || ''}</div>
                        `;
                        fileList.appendChild(row);
                    });
                }

                if (emptyState) {
                    emptyState.style.display = summary.count ? 'none' : 'grid';
                }

                if (payloadInput) payloadInput.value = JSON.stringify(state);
            };

            fillFromState();

            form.addEventListener('submit', () => {
                fillFromState();
                state.note = state.note || {};
                state.note.status = 'active';
                if (previewSelect) {
                    state.note.preview_pages = previewSelect.value;
                }
                writeState(state);
            });

            if (previewSelect) {
                previewSelect.addEventListener('change', () => set('note.preview_pages', previewSelect.value));
            }
        };

        const syncPage = () => {
            if (step === 1) fillStepOne();
            if (step === 2) bindStepTwo();
            if (step === 3) bindStepThree();
            if (step === 4) fillStepFour();
            if (step === 5) bindStepFive();
        };

        syncPage();
        if (step === 1) bindStepOne();
        if (step === 4) bindStepFour();

        writeState(state);
    })();
</script>
