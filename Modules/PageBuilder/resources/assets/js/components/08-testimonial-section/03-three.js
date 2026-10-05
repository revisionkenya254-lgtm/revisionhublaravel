import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/default-avatar.png';
import mediaImage from '@images/sections/testimonial-three.webp';


export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "testimonial__area-three youga_testimonial section-py-140 testimonial__bg-two" },
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
                    SVGInject(document.querySelectorAll("img.injectable"));
                    var testimonialSwiper = new Swiper('.testimonial-active-three', {
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
                },
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
                        <div class="row align-items-center justify-content-center">
                            <div class="col-lg-6 col-md-10">
                                <div class="testimonial__img">
                                    <img src="${placeholderImage}"
                                        alt="img">
                                    <div class="shape">
                                        <img src="${baseUrl}/frontend/img/others/testimonial_img_shape.svg"
                                            alt="shape" class="rotateme">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="testimonial__item-three">
                                    <div class="icon">
                                        <img src="${baseUrl}/frontend/img/icons/testi_icon.svg" alt=""
                                            class="injectable">
                                    </div>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                    </div>
                                    <p>“ I was hesitant about online learning at first, but this platform has completely changed my mind. The courses are well-organized and informative, ”</p>
                                    <div class="testimonial__bottom">
                                        <h4 class="title">Brooklyn Simmons</h4>
                                        <span>Engineer</span>
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
                    <div class="swiper-container testimonial-active-three">
                        <div class="swiper-wrapper">
                            <!-- DYNAMIC_PART_START:testimonial-three total=${this.get('total')} -->
                                ${this.generateHTML(parseInt(this.get('total')))}
                            <!-- DYNAMIC_PART_END -->
                        </div>
                        <div class="testimonial-pagination"></div>
                    </div>
                </div>
                <div class="testimonial__shape">
                    <img src="${baseUrl}/frontend/img/others/h4_testimonial_shape.svg" alt="shape" class="rotateme">
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