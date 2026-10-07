(() => {
    const form = document.querySelector('[data-course-builder]');
    if (!form) return;

    let step = 1;
    const panels = [...form.querySelectorAll('[data-step]')];
    const markers = [...document.querySelectorAll('[data-step-marker]')];
    const back = form.querySelector('[data-back]');
    const next = form.querySelector('[data-next]');
    const submit = form.querySelector('[data-submit]');
    const alert = document.querySelector('[data-form-alert]');
    const taxonomyElement = document.querySelector('[data-course-taxonomy]');
    const taxonomy = taxonomyElement ? JSON.parse(taxonomyElement.textContent) : {};
    const educationLevel = form.elements.education_level;
    const classGrade = form.elements.class_grade;
    const subject = form.elements.subject;
    const examCategory = form.elements.exam_category;
    const categoryId = form.elements.category;
    const classGradeLabel = document.querySelector('[data-class-grade-label]');
    const subjectLabel = document.querySelector('[data-subject-label]');

    const normalize = value => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    const matchesNode = (node, value) => normalize(node?.label) === normalize(value) || normalize(node?.slug) === normalize(value);
    const findNode = (nodes, value) => {
        for (const node of nodes || []) {
            if (matchesNode(node, value)) return node;
            const child = findNode(node.children, value);
            if (child) return child;
        }
        return null;
    };
    const addOptions = (select, values, selected, placeholder) => {
        if (!select) return;
        select.innerHTML = '';
        select.add(new Option(placeholder, ''));
        (values || []).forEach(value => {
            const label = typeof value === 'string' ? value : value.label;
            const option = new Option(label, label, false, normalize(label) === normalize(selected));
            select.add(option);
        });
        if (!select.value && values?.length === 1) select.value = typeof values[0] === 'string' ? values[0] : values[0].label;
    };
    const currentRoot = () => (taxonomy.tree || []).find(node => matchesNode(node, educationLevel?.value));
    const currentGradeNode = root => (root?.children || []).find(node => matchesNode(node, classGrade?.value));
    const syncTaxonomyLabels = () => {
        const higherEducation = /tvet|university|college|certificate|diploma|undergraduate|professional|tertiary|higher education/i.test(educationLevel?.value || '');
        if (classGradeLabel) classGradeLabel.textContent = higherEducation ? 'School of *' : 'Class / Grade *';
        if (subjectLabel) subjectLabel.textContent = higherEducation ? 'Course *' : 'Subject *';
    };
    const syncCategoryId = () => {
        const root = currentRoot();
        const grade = currentGradeNode(root);
        const subjectNode = (grade?.children || []).find(node => matchesNode(node, subject?.value));
        categoryId.value = subjectNode?.id || grade?.id || root?.id || '';
    };
    const populateSubjects = selected => {
        const root = currentRoot();
        const grade = currentGradeNode(root);
        const treeSubjects = grade?.children || [];
        const fallback = taxonomy.subjectsByLevel?.[educationLevel?.value] || taxonomy.subjects || [];
        addOptions(subject, treeSubjects.length ? treeSubjects : fallback, selected, 'Choose subject');
        syncCategoryId();
    };
    const populateExamCategories = selected => {
        const values = taxonomy.examCategoriesByLevel?.[educationLevel?.value]
            || taxonomy.examCategoriesByLevel?.default
            || [];
        addOptions(examCategory, values, selected, 'Choose exam category');
    };
    const populateGrades = (selected, selectedSubject) => {
        const root = currentRoot();
        const treeGrades = root?.children || [];
        const fallback = taxonomy.classGradesByLevel?.[educationLevel?.value]
            || taxonomy.classGradesByLevel?.default
            || [];
        addOptions(classGrade, treeGrades.length ? treeGrades : fallback, selected, 'Choose class or grade');
        populateSubjects(selectedSubject);
        syncCategoryId();
    };

    if (educationLevel && classGrade && subject) {
        const selected = taxonomy.selected || {};
        educationLevel.addEventListener('change', () => {
            syncTaxonomyLabels();
            populateGrades('', '');
            populateExamCategories('');
        });
        classGrade.addEventListener('change', () => populateSubjects(''));
        subject.addEventListener('change', syncCategoryId);
        syncTaxonomyLabels();
        populateGrades(selected.classGrade, selected.subject);
        populateExamCategories(selected.examCategory);
        syncCategoryId();
    }

    const show = value => {
        step = value;
        panels.forEach(panel => {
            const active = Number(panel.dataset.step) === step;
            panel.hidden = !active;
            panel.classList.toggle('active', active);
        });
        markers.forEach(marker => {
            const number = Number(marker.dataset.stepMarker);
            marker.classList.toggle('active', number === step);
            marker.classList.toggle('done', number < step);
        });
        back.hidden = step === 1;
        next.hidden = step === 4;
        submit.hidden = step !== 4;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const validate = () => {
        let valid = true;
        const current = panels.find(panel => Number(panel.dataset.step) === step);
        current.querySelectorAll('[required]').forEach(field => {
            field.classList.remove('is-invalid');
            if (!field.checkValidity()) {
                valid = false;
                field.classList.add('is-invalid');
            }
        });
        if (!valid) current.querySelector('.is-invalid')?.focus();
        return valid;
    };

    const updateReview = () => {
        document.querySelector('[data-review-title]').textContent = form.title.value.trim() || 'Untitled course';
        document.querySelector('[data-review-description]').textContent = form.description.value.trim() || '—';
        document.querySelector('[data-review-duration]').textContent = form.course_duration.value ? `${form.course_duration.value} minutes` : '—';
        document.querySelector('[data-review-category]').textContent = [educationLevel?.value, classGrade?.value, subject?.value].filter(Boolean).join(' › ') || '—';
        document.querySelector('[data-review-access]').textContent = form.access_type.value === 'paid' ? 'Paid course' : 'Free course';
    };

    next.addEventListener('click', () => {
        if (!validate()) return;
        if (step === 3) updateReview();
        show(Math.min(4, step + 1));
    });
    back.addEventListener('click', () => show(Math.max(1, step - 1)));

    form.querySelectorAll('[name="access_type"]').forEach(radio => radio.addEventListener('change', () => {
        form.querySelectorAll('.rh-access').forEach(card => card.classList.toggle('selected', card.querySelector('input').checked));
        const paid = form.access_type.value === 'paid';
        form.querySelector('[data-pricing-fields]').hidden = !paid;
        form.price.value = paid ? (form.price.value || '') : '0';
    }));

    form.querySelector('[data-video-source]').addEventListener('change', event => {
        const upload = event.target.value === 'bunny_stream';
        form.querySelector('[data-video-upload]').hidden = !upload;
        form.querySelector('[data-video-link]').hidden = upload;
    });
    form.thumbnail_file.addEventListener('change', event => {
        document.querySelector('[data-thumbnail-name]').textContent = event.target.files[0]?.name || '';
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!validate()) return;
        submit.disabled = true;
        alert.hidden = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await response.json();
            if (!response.ok) {
                const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Unable to create the course.'];
                throw new Error(messages.join(' '));
            }
            alert.classList.add('success');
            alert.textContent = data.message;
            alert.hidden = false;
            window.location.assign(data.redirect);
        } catch (error) {
            alert.classList.remove('success');
            alert.textContent = error.message;
            alert.hidden = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            submit.disabled = false;
        }
    });
})();
