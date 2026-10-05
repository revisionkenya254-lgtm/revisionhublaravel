import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/testi_author01.webp';
import mediaImage from '@images/sections/testimonial-four.webp';

const generateHTML = (total = 4) => {
    let html = '';
    for (let i = 0; i < total; i++) {
        html += `
        <div class="swiper-slide">
            <div class="testimonial__item-two testimonial__item-five">
                <div class="testimonial__content-two">
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p>“ when an unknown printer took alley offerer area type and scrambled to make a type specimen book has ”</p>
                </div>
                <div class="testimonial__author testimonial__author-two">
                    <div class="testimonial__author-thumb testimonial__author-thumb-two">
                        <img src="${placeholderImage}" alt="img">
                    </div>
                    <div class="testimonial__author-content testimonial__author-content-two">
                        <h2 class="title">Wade Warren</h2>
                        <span>Designer</span>
                    </div>
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
                attributes: { class: "testimonial__area-six section-py-140 testimonial__bg-three" },
                traits: [
                    { name: 'total', label: 'Total Testimonial', type: 'number', min: 1, changeProp: true },
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                // Default values
                total: 4,
                sub_title: 'Our Testimonials',
                title: 'What Students Think and Say About Us',
                background_img: `${baseUrl}/frontend/img/bg/h8_testimonial_bg.jpg`,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });

                    var testimonialSwiper = new Swiper('.testimonial-active-five', {
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
                    aosAnimation();
                    function aosAnimation() {
                        AOS.init({
                            duration: 1000,
                            mirror: true,
                            once: true,
                            disable: 'mobile',
                        });
                    }
                },
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:total change:sub_title change:title change:background_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },
            updateContent() {
                const html = `
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-xl-5 col-lg-6 col-md-8">
                            <div class="section__title mb-50">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title">${this.get('title')}</h2>
                            </div>
                        </div>
                        <div class="col-xl-7 col-lg-6 col-md-4">
                            <div class="testimonial__nav-two">
                                <button class="testimonial-button-prev"><i class="flaticon-arrow-right"></i></button>
                                <button class="testimonial-button-next"><i class="flaticon-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-container testimonial-active-five">
                        <div class="swiper-wrapper">
                            <!-- DYNAMIC_PART_START:testimonial-four total=${this.get('total')} -->
                                ${generateHTML(parseInt(this.get('total')))}
                            <!-- DYNAMIC_PART_END -->
                        </div>
                    </div>
                </div>
                <div class="testimonial__shape-wrap-two">
                    <img src="${baseUrl}/frontend/img/others/h8_testimonial_shape01.svg" alt="shape"
                        data-aos="fade-down-left" data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/h8_testimonial_shape02.svg" alt="shape" data-aos="fade-up-right"
                        data-aos-delay="400">
                </div>
                `;
                // Update the components
                this.components(html);
                this.addAttributes({ 'data-background': this.get('background_img') || '' });
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