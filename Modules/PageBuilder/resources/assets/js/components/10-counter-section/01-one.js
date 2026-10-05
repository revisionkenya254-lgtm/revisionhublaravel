import { registerComponent } from '@utils/registerComponent';
import mediaImage from '@images/sections/counter.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "fact__area" },
                traits: [
                    {
                        type: 'accordion',
                        label: 'Counters',
                        name: 'counters',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', placeholder: 'Title', default: "New Counter" },
                            { name: 'count', type: 'number', placeholder: 'Count', default: 99 },
                            { name: 'icon', type: 'text', placeholder: 'Icon (%, +, k)', default: "%" }
                        ]
                    }
                ],
                counters: [
                    { title: 'Active Students', count: 45, icon: "k+" },
                    { title: 'Faculty Courses', count: 89, icon: "+" },
                    { title: 'Best Professors', count: 156, icon: "k" },
                    { title: 'Award Achieved', count: 42, icon: "k" },
                ],
            },

            init() {
                const wrapper = this;

                // Use arrow function to preserve 'this' context
                wrapper.on('change:counters',
                    () => wrapper.updateContent()
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const countersHtml = (this.get('counters') || []).map(counter => `
                    <div class="col-lg-3 col-sm-6">
                        <div class="fact__item">
                            <h2 class="count">
                                <span class="odometer" data-count="${counter.count}">${counter.count}</span>${counter.icon}
                            </h2>
                            <p>${counter.title}</p>
                        </div>
                    </div>
                `).join('');

                const html = `
                    <div class="container">
                        <div class="fact__inner-wrap">
                            <div class="row">
                                ${countersHtml}
                            </div>
                        </div>
                    </div>
                `;
                this.components(html);
                this.applyRules(this, true);
            },
            // Recursive function to set editable/draggable/selectable etc.
            applyRules(comp, root = false) {
                comp.set({
                    editable: false,
                    draggable: root,
                    droppable: false,
                    copyable: root,
                    selectable: root,
                });
                comp.components().forEach(child => this.applyRules(child));
            },
        },
    });


};