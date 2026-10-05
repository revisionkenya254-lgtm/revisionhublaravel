import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/instructor.webp';
import mediaImage from '@images/sections/testimonial-six.webp';


export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "home_kindergarten testimonial__area-four tg-motion-effects" },
                traits: [
                    { name: 'total', label: 'Total Testimonial', type: 'number', min: 1, changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                // Default values
                total: 4,
                background_img: `${baseUrl}/frontend/img/bg/h4_testimonial_bg.jpg`,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
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
                wrapper.on('change:total change:background_img', () => {
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
                        <div class="container">
                            <div class="row align-items-center justify-content-center">
                                <div class="col-lg-7 order-0 order-lg-2">
                                    <div class="testimonial__item-four">
                                        <div class="rating">
                                            <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                        </div>
                                        <p>“ I was hesitant about online learning at first, but this platform has completely changed my mind. The courses are well-organized and informative, ”
                                        </p>
                                        <div class="testimonial__bottom-two">
                                            <h4 class="title">Brooklyn Simmons</h4>
                                            <span>Engineer</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-5 col-md-7">
                                    <div class="testimonial__img-two">
                                        <img src="${placeholderImage}" alt="img">
                                        <div class="shape">
                                            <img src="${baseUrl}/frontend/img/others/h5_testimonial_shape01.svg"
                                                alt="shape" class="alltuchtopdown">
                                            <img src="${baseUrl}/frontend/img/others/h5_testimonial_shape02.svg"
                                                alt="shape" class="tg-motion-effects4">
                                            <img src="${baseUrl}/frontend/img/others/h5_testimonial_shape03.svg"
                                                alt="shape" class="tg-motion-effects3">
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
                <div class="testimonial__bg-shape-one">
                    <img src="${baseUrl}/frontend/img/others/h5_testimonial_bg_shape01.svg" class="injectable">
                </div>
                <div class="testimonial__bg-shape-two">
                    <img src="${baseUrl}/frontend/img/others/h5_testimonial_bg_shape02.svg" class="injectable">
                </div>


                <div class="swiper-container testimonial-active-four">
                    <div class="swiper-wrapper">
                        <!-- DYNAMIC_PART_START:testimonial-six total=${this.get('total')} -->
                            ${this.generateHTML(parseInt(this.get('total')))}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                    <div class="testimonial-pagination testimonial-pagination-two"></div>
                </div>

                <div class="testimonial__shape-wrap">
                    <img src="${baseUrl}/frontend/img/others/h5_testimonial_shape04.svg" alt="shape" data-aos="fade-up"
                        data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/h5_testimonial_shape05.svg" alt="shape"
                        data-aos="fade-down-left" data-aos-delay="400">
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