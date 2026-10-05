import { registerComponent } from '@utils/registerComponent';
import mediaImage from '@images/sections/counter-two.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "fact__area-two" },
                traits: [
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
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
                title: 'Thousands of <span class="highlight">courses</span> authored by industry experts',
                counters: [
                    { title: 'Faculty Courses', count: 800, icon: "+" },
                    { title: 'Best Professors', count: 100, icon: "+" },
                    { title: 'Active Students', count: 3000, icon: "+" },
                ],
            },

            init() {
                const wrapper = this;

                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:counters',
                    () => wrapper.updateContent()
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const countersHtml = (this.get('counters') || []).map(counter => `
                    <div class="fact__item">
                        <h2 class="count">${counter.count}<span class="odometer" data-count="${counter.count}"></span>${counter.icon}</h2>
                        <p>${counter.title}</p>
                    </div>
                `).join('');

                const html = `
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="fact__inner-wrap-two">
                                    <div class="section__title white-title mb-30">
                                        <h2 class="title">${this.get('title')}</h2>
                                    </div>
                                    <div class="fact__item-wrap">
                                        ${countersHtml}
                                    </div>
                                </div>
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