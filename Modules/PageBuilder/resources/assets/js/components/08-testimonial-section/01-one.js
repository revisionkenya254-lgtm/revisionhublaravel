import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/testi_author01.webp';
import mediaImage from '@images/sections/testimonial.webp';

const generateHTML = (total = 4) => {
    let html = '';
    for (let i = 0; i < total; i++) {
        html += `
        <div class="swiper-slide">
            <div class="testimonial__item">
                <div class="testimonial__item-top">
                    <div class="testimonial__author">
                        <div class="testimonial__author-thumb">
                            <img src="${placeholderImage}" alt="img">
                        </div>
                        <div class="testimonial__author-content">
                            <div class="rating">
                                <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                            </div>
                            <h2 class="title">Wade Warren</h2>
                        </div>
                    </div>
                </div>
                <div class="testimonial__content">
                    <p>“ when an unknown printer took alley offerer area type and scrambled to make a type specimen book has ”</p>
                </div>
            </div>
        </div>
        `;
    }
    return html;
};
export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "testimonial__area section-py-120" },
                traits: [
                    { name: 'total', label: 'Total Testimonial', type: 'number', min: 1, changeProp: true },
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                ],
                // Default values
                total: 4,
                sub_title: 'Our Testimonials',
                title: 'What Students Think and Say About Us',
                script: function () {
                    var testimonialSwiper = new Swiper('.testimonial-swiper-active', {
                        // Optional parameters
                        slidesPerView: 3,
                        spaceBetween: 30,
                        observer: true,
                        observeParents: true,
                        loop: true,
                        breakpoints: {
                            '1500': {
                                slidesPerView: 3,
                            },
                            '1200': {
                                slidesPerView: 3,
                            },
                            '992': {
                                slidesPerView: 3,
                                spaceBetween: 24,
                            },
                            '768': {
                                slidesPerView: 2,
                                spaceBetween: 24,
                            },
                            '576': {
                                slidesPerView: 1,
                            },
                            '0': {
                                slidesPerView: 1,
                            },
                        },
                        // Navigation arrows
                        navigation: {
                            nextEl: ".testimonial-button-next",
                            prevEl: ".testimonial-button-prev",
                        },
                    });
                },
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:total change:sub_title change:title', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },
            updateContent() {
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-5">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title">${this.get('title')}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="testimonial__item-wrap">
                                <div class="swiper-container testimonial-swiper-active">
                                    <div class="swiper-wrapper">
                                        <!-- DYNAMIC_PART_START:testimonial total=${this.get('total')} -->
                                            ${generateHTML(parseInt(this.get('total')))}
                                        <!-- DYNAMIC_PART_END -->
                                    </div>
                                </div>
                                <div class="testimonial__nav">
                                    <button class="testimonial-button-prev"><i class="flaticon-arrow-right"></i></button>
                                    <button class="testimonial-button-next"><i class="flaticon-arrow-right"></i></button>
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