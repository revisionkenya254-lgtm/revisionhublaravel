import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/placeholder.jpg';
import blogManagerImg from '@images/sections/course.webp';

const generateCourseHTML = () => {
    let html = '';
    for (let i = 0; i < 5; i++) {
        html += `
            <div class="swiper-slide">
                <div class="courses__item shine__animate-item">
                    <div class="courses__item-thumb">
                        <a href="courses" class="shine__animate-link">
                            <img src="${placeholderImage}" alt="img">
                        </a>
                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two" aria-label="WishList"><i class="far fa-heart"></i></a>
                    </div>
                    <div class="courses__item-content">
                        <ul class="courses__item-meta list-wrap">
                            <li class="courses__item-tag">
                                <a href="courses">Category</a>
                            </li>
                            <li class="avg-rating"><i class="fas fa-star"></i> (5 Reviews)</li>
                        </ul>
                        <h5 class="title"><a href="courses">Course Title</a></h5>
                        <p class="author">By <a href="#">John Doe</a></p>
                        <div class="courses__item-bottom">
                            <div class="button">
                                <a href="courses">
                                    <span class="text">${i % 2 === 0 ? 'Add To Cart' : 'Enroll Now'}</span>
                                    <i class="flaticon-arrow-right"></i>
                                </a>
                            </div>
                            <h5 class="price">$99.00</h5>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    return html;
};

const coursesNav = `
    <div class="courses__nav">
        <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
        <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
    </div>
`;

const renderTabPane = (id, title, active = false) => `
    <div class="tab-pane fade ${active ? 'show active' : ''}" id="${id}-tab-pane" role="tabpanel" aria-labelledby="${id}-tab" tabindex="0">
        <div class="swiper courses-swiper-active">
            <div class="swiper-wrapper">
                ${generateCourseHTML()}
            </div>
        </div>
        ${coursesNav}
    </div>
`;
export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "courses-area section-pt-120 section-pb-90" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],

                // Default values
                sub_title: 'Top Class Courses',
                title: "Explore Our World's Best Courses",
                description: 'When known printer took a galley of type scrambled make',
                background_img: `${baseUrl}/frontend/img/bg/courses_bg.jpg`,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    var coursesSwiper = new Swiper('.courses-swiper-active', {
                        // Optional parameters
                        slidesPerView: 4,
                        spaceBetween: 30,
                        observer: true,
                        observeParents: true,
                        loop: false,
                        breakpoints: {
                            '1400': {
                                slidesPerView: 4,
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
                            nextEl: ".courses-button-next",
                            prevEl: ".courses-button-prev",
                        },
                    });
                },
            },

            init() {
                const wrapper = this;
                // Store swiper instance reference
                this.swiperInstance = null;
                wrapper.on('change:sub_title change:title change:description change:background_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                wrapper.updateContent();
            },
            updateContent() {
                const html = `
                    <div class="container">
                        <div class="section__title-wrap">
                            <div class="row justify-content-center">
                                <div class="col-lg-6">
                                    <div class="section__title text-center">
                                        <span class="sub-title" data-editable="true">${this.get('sub_title')}</span>
                                        <h2 class="title" data-editable="true">${this.get('title')}</h2>
                                        <p class="desc" data-editable="true">${this.get('description')}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course -->
                        <div class="row justify-content-center mb-60">
                            <div class="courses__nav">
                                <ul class="nav nav-tabs" id="courseTab" role="tablist">
                                    ${[
                        { id: "all", label: "All Course" },
                        { id: "design", label: "Web Design" },
                        { id: "business", label: "Business" },
                        { id: "development", label: "Development" }
                    ].map((item, i) => `
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link ${i === 0 ? 'active' : ''}" id="${item.id}-tab" data-bs-toggle="tab" data-bs-target="#${item.id}-tab-pane"
                                                type="button" role="tab" aria-controls="${item.id}-tab-pane" aria-selected="${i === 0}">
                                                ${item.label}
                                            </button>
                                        </li>
                                    `).join("")}
                                </ul>
                            </div>
                        </div>
                        <div class="tab-content" id="courseTabContent">
                            ${renderTabPane('all', 'All Courses', true)}
                            ${renderTabPane('design', 'Design')}
                            ${renderTabPane('business', 'Business')}
                            ${renderTabPane('development', 'Development')}
                        </div>
                        <!-- DYNAMIC_PART_END -->
                    </div>
                `;
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
