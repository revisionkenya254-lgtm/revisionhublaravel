import { registerComponent } from '@utils/registerComponent';

import instructorImage from '@images/others/default-avatar.png';
import blogManagerImg from '@images/sections/instructor.webp';

const generateHTML = (total = 4) => {
    let html = '';
    for (let i = 0; i < total; i++) {
        html += `
            <div class="col-sm-6">
                <div class="instructor__item">
                    <div class="instructor__thumb">
                        <a href="#"><img src="${instructorImage}" alt="img"></a>
                    </div>
                    <div class="instructor__content">
                        <h2 class="title"><a href="#">John Doe</a></h2>
                        <span class="designation">UX Design Lead</span>
                        <p class="avg-rating">
                            <i class="fas fa-star"></i>
                            (5 Ratings)
                        </p>
                        <div class="instructor__social">
                            <ul class="list-wrap">
                                <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                                <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                                <li><a href="#"><i class="fab fa-whatsapp"></i></a></li>
                                <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    return html;
};
export default (editor,{folderName, fileName},baseUrl ) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "instructor__area" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                ],

                // Default values
                sub_title: 'Skilled Introduce',
                title: 'Our Top Class & Expert Instructors in One Place',
                description: 'when an unknown printer took a galley of type and scrambled makespecimen book has survived not only five centuries',
                btnLink: '/all-instructors',
                btnText: 'See All Instructors',
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title change:description change:btnLink change:btnText', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-xl-4">
                                <div class="instructor__content-wrap">
                                    <div class="section__title mb-15">
                                        <span class="sub-title">${this.get('sub_title')}</span>
                                        <h2 class="title">${this.get('title')}</h2>
                                    </div>
                                    <p>${this.get('description')}</p>
                                    <div class="tg-button-wrap">
                                        <a href="${this.get('btnLink')}" class="btn arrow-btn">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
                                    </div>
                                </div>
                            </div>
                            <!-- DYNAMIC_PART_START:instructor -->
                            <div class="col-xl-8">
                                <div class="instructor__item-wrap">
                                    <div class="row">
                                        ${generateHTML()}
                                    </div>
                                </div>
                            </div>
                            <!-- DYNAMIC_PART_END -->
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
