import { registerComponent } from '@utils/registerComponent';
import faqImg from '@images/others/h5_faq_img.webp';
import mediaImage from '@images/sections/faq-four.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "faq__area-two tg-motion-effects section-py-140" },
                traits: [
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
                sub_title: 'Faq’s',
                title: 'Why Our Schools are the Right Fit for Your Child?',
                description: 'Groove’s intuitive shared inbox makes it easy for team members to organize, prioritize and.In this episod.',
                faq_img: faqImg,
                faqs: [
                    { title: 'What kind of courses do you offer?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                    { title: 'Who are your instructors?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                    { title: 'How much do your courses cost?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                    { title: 'How does online learning platform work?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                ],
                script: function () {
                    SVGInject(document.querySelectorAll("img.injectable"));
                    aosAnimation();
                    function aosAnimation() {
                        AOS.init({
                            duration: 1000,
                            mirror: true,
                            once: true,
                            disable: 'mobile',
                        });
                    }
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title change:description change:faq_img change:faqs', () => {
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
                            <button class="accordion-button ${i == 0 ? '' : 'collapsed'}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse_${i}" aria-expanded="true"
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
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6 col-md-8 order-0 order-lg-2">
                            <div class="faq__img-three">
                                <div class="faq__mask-img">
                                    <img src="${this.get('faq_img')}" alt="${this.get('title')}">
                                </div>
                                <div class="faq__img-shape">
                                    <img src="${baseUrl}/frontend/img/others/h5_faq_img_shape.svg" class="injectable">
                                </div>
                                <div class="shape shape-one" data-aos="fade-down-left" data-aos-delay="400">
                                    <img src="${baseUrl}/frontend/img/others/h5_faq_shape02.svg" alt="shape"
                                        class="tg-motion-effects3">
                                </div>
                                <div class="shape shape-two" data-aos="fade-up-left" data-aos-delay="400">
                                    <img src="${baseUrl}/frontend/img/others/h5_faq_shape03.svg" alt="shape"
                                        class="tg-motion-effects4">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="faq__content-two">
                                <div class="section__title mb-15">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title bold">${this.get('title')}</h2>
                                </div>
                                <p>${this.get('description')}}</p>
                                <div class="faq__wrap faq__wrap-two">
                                    <div class="accordion" id="accordionExample">
                                        ${generateHTML}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="faq__shape">
                    <img src="${baseUrl}/frontend/img/others/h5_faq_shape01.svg" alt="shape" class="tg-motion-effects3">
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