import { registerComponent } from '@utils/registerComponent';
import faqImg from '@images/others/faq_img.png';
import mediaImage from '@images/sections/faq.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "faq__area" },
                traits: [
                    { name: 'circle_text', label: 'Circle Text', type: 'textarea', changeProp: true },
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'faq_img', label: 'Image', type: 'image-upload', changeProp: true },
                    {
                        name: 'faqs', label: "FAQs", type: 'accordion', changeProp: true,
                        inputsConfig: [
                            { name: 'title', type: 'text', placeholder: 'Question', default: "New Question?" },
                            { name: 'answer', type: 'textarea', placeholder: 'Answer', default: "Lorem Ipsum is simply dummy text of the printing and typesetting industry." },
                        ]
                    }
                ],
                // Default values
                circle_text: "* Education * System * can * Make * Change *",
                sub_title: 'Faq’s',
                title: 'Start Learning From <br> World’s Pro Instructors',
                description: 'Groove’s intuitive shared inbox makes it easy for team members to organize, prioritize and.In this episode.',
                faq_img: faqImg,
                faqs: [
                    { title: 'What’s Skillgrow Want to give you?', answer: "Groove’s intuitive shared inbox makes it easy for team members organize prioritize and.In this episode.urvived not only five centuries.Edhen an unknown printer took a galley of type and scrambl" },
                    { title: 'What’s Skillgrow Want to give you?', answer: "Groove’s intuitive shared inbox makes it easy for team members organize prioritize and.In this episode.urvived not only five centuries.Edhen an unknown printer took a galley of type and scrambl" },
                    { title: 'What’s Skillgrow Want to give you?', answer: "Groove’s intuitive shared inbox makes it easy for team members organize prioritize and.In this episode.urvived not only five centuries.Edhen an unknown printer took a galley of type and scrambl" },
                    { title: 'What’s Skillgrow Want to give you?', answer: "Groove’s intuitive shared inbox makes it easy for team members organize prioritize and.In this episode.urvived not only five centuries.Edhen an unknown printer took a galley of type and scrambl" },
                ],
                script: function () {
                    $(".curved-circle").circleType({
                        position: "absolute",
                        dir: 1,
                        radius: 280,
                        forceHeight: true,
                        forceWidth: true,
                    });
                },
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:circle_text change:sub_title change:title change:description change:faq_img change:faqs', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },
            updateContent() {
                const generateHTML = (this.get('faqs') || []).map((item, i) => `
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button ${i == 0 ? '' : 'collapsed'}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse_${i}" aria-expanded="true"
                                aria-controls="collapse_${i}">
                                ${item.title}
                            </button>
                        </h2>
                        <div id="collapse_${i}"
                            class="accordion-collapse collapse ${i == 0 ? 'show' : ''}"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <p>${item.answer}</p>
                            </div>
                        </div>
                    </div>
                `).join('');

                const html = `
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="faq__img-wrap tg-svg">
                                    <div class="faq__round-text">
                                        <div class="curved-circle">
                                            ${this.get('circle_text')}
                                        </div>
                                    </div>
                                    <div class="faq__img">
                                        <img src="${this.get('faq_img')}" alt="${this.get('title')}">
                                        <div class="shape-one">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="243" height="201" viewBox="0 0 243 201" fill="none" data-inject-url="${baseUrl}/frontend/img/others/faq_shape01.svg" class="injectable tg-motion-effects4">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M33.4888 2.33732C51.0788 -4.90402 74.7375 10.3492 95.1583 10.1059C112.054 9.90458 124.917 -1.94773 142.286 1.04108C161.229 4.30087 182.771 11.4675 196.311 27.5126C209.823 43.525 204.073 64.7482 211.768 83.3776C219.978 103.254 244.371 120.756 242.796 140.051C241.244 159.064 219.234 165.093 204.324 174.576C191.463 182.756 177.07 187.574 161.728 191.613C144.966 196.024 128.585 200.247 110.135 199.366C85.6793 198.199 52.9607 207.808 36.8703 185.654C19.1764 161.292 51.8095 136.455 44.5608 109.082C38.4883 86.1502 3.04428 71.7681 0.682568 48.9991C-1.5703 27.2795 15.2076 9.8632 33.4888 2.33732Z" fill="currentcolor"></path>
                                            </svg>
                                        </div>
                                        <div class="shape-two">
                                            <span class="svg-icon" id="faq-svg"
                                                data-svg-icon="${baseUrl}/frontend/img/others/faq_shape02.svg"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="faq__content">
                                    <div class="section__title pb-10">
                                        <span class="sub-title">${this.get('sub_title')}</span>
                                        <h2 class="title">${this.get('title')}</h2>
                                    </div>
                                    <p>${this.get('description')}</p>
                                    <div class="faq__wrap">
                                        ${generateHTML}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                // Update the components
                this.components(html);
                // Apply rules recursively
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