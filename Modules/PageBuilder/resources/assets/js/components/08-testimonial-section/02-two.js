import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/testimonial-1.webp';
import mediaImage from '@images/sections/testimonial-two.webp';


export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "testimonial__area-five section-pb-120" },
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
                    var testimonialSwiper = new Swiper('.testimonial-active-four', {
                        // Optional parameters
                        slidesPerView: 1,
                        spaceBetween: 30,
                        observer: true,
                        observeParents: true,
                        loop: true,
                        breakpoints: {
                            '1500': {
                                slidesPerView: 1,
                            },
                            '1200': {
                                slidesPerView: 1,
                            },
                            '992': {
                                slidesPerView: 1,
                                spaceBetween: 24,
                            },
                            '768': {
                                slidesPerView: 1,
                                spaceBetween: 24,
                            },
                            '576': {
                                slidesPerView: 1,
                            },
                            '0': {
                                slidesPerView: 1,
                            },
                        },
                        // If we need pagination
                        pagination: {
                            el: '.testimonial-pagination',
                            clickable: true,
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
                wrapper.on('change:total change:sub_title change:title', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },
            generateHTML(total) {
                let html = '';
                for (let i = 0; i < total; i++) {
                    html += `
                    <div class="swiper-slide">
                        <div class="row align-items-center justify-content-center testimonial__content-four">
                            <div class="col-xl-6 col-lg-6 col-md-8">
                                <div class="testimonial__img-three testimonial__img-four tg-svg">
                                    <img class="business_testimonial_img" src="${placeholderImage}"
                                        alt="img">
                                    <div class="banner__review" data-aos="fade-right" data-aos-delay="400">
                                        <div class="icon">
                                            <img src="${baseUrl}/frontend/img/icons/star.svg" alt="star"
                                                class="injectable">
                                        </div>
                                        <h6 class="title">5/5
                                            <span>Real Reviews</span>
                                        </h6>
                                    </div>
                                    <div class="testimonial__img-icon">
                                        <img src="${baseUrl}/frontend/img/icons/quote02.svg" alt="quote"
                                            class="injectable">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6">
                                <div class="testimonial__content-three">
                                    <div class="section__title mb-25">
                                        <span class="sub-title">${this.get('sub_title')}</span>
                                        <h2 class="title bold">${this.get('title')}</h2>
                                    </div>

                                    <div class="testimonial__item-four">
                                        <div class="rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <p>“ I was hesitant about online learning at first, but this platform has completely changed my mind. The courses are well-organized and informative, ”</p>
                                        <div class="testimonial__bottom-two">
                                            <h4 class="title">Brooklyn Simmons</h4>
                                            <span>Engineer</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;
                }
                return html;
            },
            updateContent() {
                const html = `
                <div class="container">
                    <div class="swiper-container testimonial-active-four business_testimonial">
                        <div class="swiper-wrapper">
                            <!-- DYNAMIC_PART_START:testimonial-two total=${this.get('total')} subTitle="${this.get('sub_title')}" title="${this.get('title')}" -->
                                ${this.generateHTML(parseInt(this.get('total')))}
                            <!-- DYNAMIC_PART_END -->
                        </div>
                        <div class="row">
                            <div class="col-xl-6 col-lg-6 ms-auto">
                                <div class="testimonial-pagination testimonial-pagination-two"></div>
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