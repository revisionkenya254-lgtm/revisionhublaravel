import { registerComponent } from '@utils/registerComponent';
import factImg from '@images/others/fact_img.webp';
import mediaImage from '@images/sections/counter-four.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "fact__area-two section-pb-140" },
                traits: [
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'image', label: 'Image', type: 'image-upload', changeProp: true },
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
                image: factImg,
                counters: [
                    { title: 'Active Students', count: 3000, icon: "+" },
                    { title: 'Best Professors', count: 100, icon: "+" },
                ],
                script: function () {
                    var $svgIconBox = $('.tg-svg');
                    $svgIconBox.each(function () {
                        var $this = $(this),
                            $svgIcon = $this.find('.svg-icon'),
                            $id = $svgIcon.attr('id'),
                            $icon = $svgIcon.data('svg-icon');
                        if ($id) {
                            var $vivus = new Vivus($id, {
                                duration: 80,
                                file: $icon,
                            });
                            $this.on('mouseenter', function () {
                                $vivus.reset().play();
                            });
                        }
                    });
                    SVGInject(document.querySelectorAll("img.injectable"));
                }
            },

            init() {
                const wrapper = this;

                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:image change:counters',
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
                                <div class="fact__img-wrap tg-svg">
                                    <img src="${this.get('image')}" alt="counter image">
                                    <div class="shape-one">
                                        <img src="${baseUrl}/frontend/img/others/fact_shape01.svg" alt="img" class="injectable">
                                    </div>
                                    <div class="shape-two">
                                        <span class="svg-icon" id="fact-btn" data-svg-icon="${baseUrl}/frontend/img/others/fact_shape02.svg"></span>
                                    </div>
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