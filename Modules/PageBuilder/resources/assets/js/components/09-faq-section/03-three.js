import { registerComponent } from '@utils/registerComponent';
import faqImg from '@images/others/h6_faq_img01.webp';
import faqImgTwo from '@images/others/h6_faq_img02.webp';
import mediaImage from '@images/sections/faq-three.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "faq__area-three tg-motion-effects section-py-140" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'counter', label: 'Counter', type: 'text', changeProp: true },
                    { name: 'counter_title', label: 'Counter Title', type: 'text', changeProp: true },
                    { name: 'faq_img', label: 'Image', type: 'image-upload', changeProp: true },
                    { name: 'faq_img_two', label: 'Image Two', type: 'image-upload', changeProp: true },
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
                counter: '45+',
                counter_title: 'Course Language',
                faq_img: faqImg,
                faq_img_two: faqImgTwo,
                faqs: [
                    { title: 'What kind of courses do you offer?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                    { title: 'Who are your instructors?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                    { title: 'How much do your courses cost?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                    { title: 'How does online learning platform work?', answer: "We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you." },
                ],
                script: function () {
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
                wrapper.on('change:sub_title change:title change:description  change:counter  change:counter_title change:faq_img change:faq_img_two change:faqs', () => {
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
                        <div class="col-lg-7 col-md-10 order-0 order-lg-2">
                            <div class="faq__img-four">
                                <div class="main-img">
                                    <img src="${this.get('faq_img')}" alt="${this.get('title')}" data-aos="fade-down"
                                        data-aos-delay="400">
                                    <img src="${this.get('faq_img_two')}" alt="${this.get('title')}" data-aos="fade-up"
                                        data-aos-delay="400">
                                </div>
                                <div class="faq__language-wrap" data-aos="fade-right" data-aos-delay="600">
                                    <h2 class="title">${this.get('counter')}</h2>
                                    <span>${this.get('counter_title')}</span>
                                </div>
                                <div class="shape">
                                    <img src="${baseUrl}/frontend/img/others/h6_faq_shape01.svg" alt="shape"
                                        class="alltuchtopdown">
                                    <img src="${baseUrl}/frontend/img/others/h6_faq_shape02.svg" alt="shape"
                                        class="tg-motion-effects4">
                                    <img src="${baseUrl}/frontend/img/others/h6_faq_shape03.svg" alt="shape"
                                        class="tg-motion-effects3">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="faq__content-two faq__content-three">
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