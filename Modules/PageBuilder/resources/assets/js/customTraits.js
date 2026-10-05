import { showToast } from './toastr.js';

export default (editor, csrfToken, baseUrl) => {
    const traitManager = editor.TraitManager;

    traitManager.addType('image-upload', {
        createInput({ trait }) {
            const component = trait.target;
            const traitName = trait.get('name');
            const initialValue = component.get(traitName) || '';

            return createImageUploadInput({
                initialValue,
                onChange: (newValue) => {
                    component.set(traitName, newValue);
                },
            });
        },

        onUpdate({ elInput, component, trait }) {
            const img = elInput.querySelector('img');
            const removeBtn = elInput.querySelector('button');
            const traitName = trait.get('name');
            const src = component.get(traitName) || '';
            img.src = src;
            removeBtn.style.display = src ? 'inline-block' : 'none';
        }
    });

    traitManager.addType('textarea', {
        createInput({ trait }) {
            const el = document.createElement('textarea');
            el.placeholder = trait.get('placeholder') || '';
            el.rows = 4;
            el.value = trait.get('value') || '';
            el.addEventListener('change', (e) => trait.set('value', e.target.value));
            return el;
        },
        onUpdate({ elInput, trait }) {
            elInput.value = trait.get('value') || '';
        }
    });

    traitManager.addType('accordion', {
        /**
         * @param {object} options - options object
         * @param {Array} options.inputs - array of "inputsConfig" like [{name, type, placeholder, className, default}]
         */
        createInput({ trait }) {
            const el = document.createElement('div');
            el.className = 'accordion-area accordion gjs-custom-trait-wrapper';
            el.id = 'faqAccordion';

            // Inputs configuration to use (could come from trait config or hardcoded here)
            // You can also pass this via trait attributes if you want
            const inputsConfig = trait.get('inputsConfig') || [
                { name: 'title', type: 'text', placeholder: 'Title', default: "Title" },
            ];

            function setupAccordionToggle() {
                el.querySelectorAll('.accordion-header button').forEach(button => {
                    button.onclick = () => {
                        const accordionCard = button.closest('.accordion-card');
                        const isActive = accordionCard.classList.contains('active');

                        el.querySelectorAll('.accordion-card').forEach(card => {
                            card.classList.remove('active');
                            const collapse = card.querySelector('.accordion-collapse');
                            collapse.classList.remove('show');
                            const btn = card.querySelector('button.accordion-button');
                            btn.classList.add('collapsed');
                            btn.setAttribute('aria-expanded', 'false');
                        });

                        if (!isActive) {
                            accordionCard.classList.add('active');
                            const collapse = accordionCard.querySelector('.accordion-collapse');
                            collapse.classList.add('show');
                            button.classList.remove('collapsed');
                            button.setAttribute('aria-expanded', 'true');
                        }
                    };
                });
            }

            const renderCounters = (items) => {
                el.innerHTML = '';

                items.forEach((item, index) => {
                    const card = document.createElement('div');
                    card.className = 'accordion-card style2';

                    // Header
                    const header = document.createElement('div');
                    header.className = 'accordion-header';
                    header.id = `collapse-item-${index}`;

                    const btn = document.createElement('button');
                    btn.className = 'accordion-button collapsed';
                    btn.type = 'button';
                    btn.setAttribute('aria-expanded', 'false');
                    btn.setAttribute('aria-controls', `collapse-${index}`);
                    btn.textContent = item.title || `Item #${index + 1}`;

                    header.appendChild(btn);

                    // Collapse container
                    const collapse = document.createElement('div');
                    collapse.id = `collapse-${index}`;
                    collapse.className = 'accordion-collapse collapse';
                    collapse.setAttribute('aria-labelledby', `collapse-item-${index}`);

                    const body = document.createElement('div');
                    body.className = 'accordion-body d-flex flex-column gap-2 gjs-custom-trait-wrapper';

                    // Dynamically create inputs based on inputsConfig
                    inputsConfig.forEach(config => {
                        let input;

                        if (config.type === 'image-upload') {
                            input = createImageUploadInput({
                                initialValue: item[config.name] || '',
                                onChange: (newValue) => {
                                    items[index][config.name] = newValue;
                                    trait.set('value', items.map(i => ({ ...i })));
                                }
                            });
                        } else if (config.type === 'textarea') {
                            input = document.createElement('textarea');
                            input.rows = config.rows || 3;
                        } else {
                            input = document.createElement('input');
                            input.type = config.type || 'text';
                        }

                        if (config.className) input.className = config.className;
                        if (config.placeholder) input.placeholder = config.placeholder;
                        input.value = item[config.name] || '';

                        input.addEventListener('input', () => {
                            items[index] = { ...items[index], [config.name]: config.type === 'number' ? parseFloat(input.value) || 0 : input.value };
                            trait.set('value', items.map(i => ({ ...i })));

                            if (config.name === 'title') {
                                btn.textContent = input.value || `Item #${index + 1}`;
                            }
                        });

                        if (config.label) {
                            const label = document.createElement('label');
                            label.textContent = config.label;
                            label.className = 'mb-2 d-block';
                            label.appendChild(input);
                            body.appendChild(label);
                        } else {
                            body.appendChild(input);
                        }
                    });

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-sm btn-danger mt-2 align-self-start';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i> Remove';
                    removeBtn.addEventListener('click', () => {
                        items.splice(index, 1);
                        renderCounters(items);
                        trait.set('value', [...items]);
                    });

                    body.appendChild(removeBtn);
                    collapse.appendChild(body);

                    card.appendChild(header);
                    card.appendChild(collapse);

                    el.appendChild(card);
                });

                // Add new item button
                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'btn btn-primary mt-3 w-100';
                addBtn.innerHTML = '<i class="fas fa-plus"></i> Add Item';
                addBtn.addEventListener('click', () => {
                    // Initialize a new item with empty or default values based on inputsConfig
                    const newItem = {};
                    inputsConfig.forEach(({ name, default: defVal, type }) => {
                        if (defVal !== undefined) {
                            newItem[name] = defVal;
                        } else if (type === 'number') {
                            newItem[name] = 0; // fallback default for numbers
                        } else {
                            newItem[name] = '';
                        }
                    });
                    items.push(newItem);
                    renderCounters(items);
                    trait.set('value', [...items]);
                });

                el.appendChild(addBtn);

                setupAccordionToggle();
            };

            el._renderCounters = renderCounters;

            const initialValue = trait.get('value') || [];
            renderCounters([...initialValue]);

            return el;
        },

        onUpdate({ elInput, trait }) {
            if (elInput && typeof elInput._renderCounters === 'function') {
                elInput._renderCounters([...trait.get('value') || []]);
            }
        }
    });

    function createImageUploadInput({ initialValue, onChange }) {
        const container = document.createElement('div');
        const input = document.createElement('input');
        const previewBox = document.createElement('div');
        const preview = document.createElement('img');
        const removeBtn = document.createElement('button');

        input.type = 'file';
        input.accept = 'image/*';

        previewBox.className = 'custom-image-preview-box m-2 d-flex items-center';
        preview.className = 'img-fluid h-auto';

        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger trait-image-remove-btn';
        removeBtn.style.display = initialValue ? 'inline-block' : 'none';

        preview.src = initialValue || '';

        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(`${baseUrl}/admin/page-builder/upload`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });

                const data = await res.json();
                if (data.success) {
                    const imageUrl = data.file_name;
                    preview.src = imageUrl;
                    removeBtn.style.display = 'inline-block';
                    onChange(imageUrl);
                } else {
                    showToast(data.message || "Upload failed", 'error');
                }
            } catch (err) {
                showToast(err.message || "Unexpected upload error", 'error');
            }
        };

        removeBtn.onclick = async () => {
            const imageUrl = preview.src;
            if (!imageUrl) return;

            const btnText = removeBtn.innerHTML;
            const loaderHTML = '<span class="spinner-border spinner-border-sm"></span>';

            removeBtn.innerHTML = loaderHTML;
            removeBtn.disabled = true;

            try {
                const res = await fetch(`${baseUrl}/admin/page-builder/delete-upload`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        file: imageUrl.split('/').pop(),
                    }),
                });

                const data = await res.json();

                if (res.ok) {
                    preview.src = '';
                    removeBtn.style.display = 'none';
                    onChange('');
                    showToast(data.message || "Image removed successfully.", 'success');
                } else {
                    showToast(data.message || "Failed to remove image.", 'error');
                }
            } catch (err) {
                showToast(err.message || "Unexpected delete error", 'error');
            } finally {
                removeBtn.innerHTML = btnText;
                removeBtn.disabled = false;
            }
        };

        container.appendChild(input);
        previewBox.appendChild(preview);
        previewBox.appendChild(removeBtn);
        container.appendChild(previewBox);

        return container;
    }
};