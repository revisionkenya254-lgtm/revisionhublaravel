import { registerComponent } from '@utils/registerComponent';

import instructorPic from '@images/others/online-instructor.webp';
import instructorImage from '@images/others/default-avatar.png';
import blogManagerImg from '@images/sections/instructor-two.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "instructor__area-three instructor__bg" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                ],

                // Default values
                sub_title: 'Skilled Introduce',
                title: 'Our Top Class & Expert Instructors in One Place',

                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    SVGInject(document.querySelectorAll("img.injectable"));
                    var categoriesSwiper = new Swiper(".instructor-nav", {
                        spaceBetween: 0,
                        slidesPerView: 6,
                        loop: true,
                        // Navigation arrows
                        navigation: {
                            nextEl: ".instructor-button-next",
                            prevEl: ".instructor-button-prev",
                        },
                    });
                },
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                    <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="section__title text-center mb-40">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title">${this.get('title')}</h2>
                            </div>
                        </div>
                    </div>
                    <!-- DYNAMIC_PART_START:instructor-two -->
                        <div class="row justify-content-center">
                            <div class="col-xl-8">
                                <div class="swiper-container instructor-active">
                                    <div class="swiper-slide">
                                        <div class="instructor__item-three">
                                            <div class="instructor__thumb-three">
                                                <img src="${instructorPic}" alt="img">
                                                <div class="shape-one">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="401" height="394" viewBox="0 0 401 394" fill="none" data-inject-url="${baseUrl}/frontend/img/instructor/h2_instructor_img_shape01.svg" class="injectable">
                                                        <path d="M25.0021 74.3114C24.9681 33.4423 58.0715 0.283875 98.9406 0.249897L326 0.0611223C366.869 0.0271443 400.028 33.1305 400.062 73.9996C400.096 114.869 366.992 148.027 326.123 148.061L99.0636 148.25C58.1946 148.284 25.0361 115.18 25.0021 74.3114Z" fill="currentcolor"></path>
                                                        <path d="M21.1658 199.323C21.1323 159.006 53.7884 126.295 94.1051 126.262L306.105 126.086C346.422 126.052 379.132 158.708 379.166 199.025C379.199 239.342 346.543 272.052 306.226 272.086L94.2265 272.262C53.9097 272.295 21.1994 239.639 21.1658 199.323Z" fill="currentcolor"></path>
                                                        <path d="M0.264675 318.34C0.230238 276.919 33.781 243.312 75.2023 243.278L325.202 243.07C366.624 243.036 400.23 276.586 400.265 318.008C400.299 359.429 366.748 393.036 325.327 393.07L75.327 393.278C33.9057 393.312 0.299112 359.762 0.264675 318.34Z" fill="currentcolor"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="instructor__content-three">
                                                <div class="ratting-wrap">
                                                    <div class="ratting">
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                    </div>
                                                    <span>(4.8 Ratings)</span>
                                                </div>
                                                <h2 class="title">William Hope</h2>
                                                <span class="designation">Digital Marketing</span>
                                                <p>SkillGro The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested.</p>
                                                <div class="instructor__social">
                                                    <ul class="list-wrap">
                                                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                                                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                                                        <li><a href="#"><i class="fab fa-whatsapp"></i></a></li>
                                                        <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                                                    </ul>
                                                </div>
                                                <div class="tg-button-wrap">
                                                    <a href="/all-instructors" class="btn arrow-btn">Join My Class <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="img" class="injectable"></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-xl-9">
                                <div class="instructor-slider-dot">
                                    <div class="swiper instructor-nav">
                                        <div class="swiper-wrapper">
                                            <div class="swiper-slide">
                                                <button><img src="${instructorPic}" alt="img"></button>
                                            </div>
                                            <div class="swiper-slide">
                                                <button><img src="${instructorPic}" alt="img"></button>
                                            </div>
                                            <div class="swiper-slide">
                                                <button><img src="${instructorPic}" alt="img"></button>
                                            </div>
                                            <div class="swiper-slide">
                                                <button><img src="${instructorPic}" alt="img"></button>
                                            </div>
                                            <div class="swiper-slide">
                                                <button><img src="${instructorPic}" alt="img"></button>
                                            </div>
                                            <div class="swiper-slide">
                                                <button><img src="${instructorPic}" alt="img"></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="instructor__nav-two">
                                        <div class="instructor-button-prev"><i class="flaticon-arrow-right"></i></div>
                                        <div class="instructor-button-next"><i class="flaticon-arrow-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <!-- DYNAMIC_PART_END -->
                    </div>
                    <div class="instructor__shape">
                        <img src="${baseUrl}/frontend/img/instructor/h2_instructor_shape.png" alt="img" class="alltuchtopdown">
                    </div>
                `;
                // Update the components
                this.components(html);
                this.addAttributes({ 'data-background': this.get('background_img') || '' });
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
