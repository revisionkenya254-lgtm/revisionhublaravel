import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/placeholder.jpg';
import blogManagerImg from '@images/sections/course-three.webp';

const generateCourseHTML = () => {
    let html = '';
    for (let i = 0; i < 5; i++) {
        html += `
            <div class="swiper-slide">
                <div class="courses__item-eight shine__animate-item">
                    <div class="courses__item-thumb-seven shine__animate-link">
                        <a href="#"><img src="${placeholderImage}" alt="img"></a>
                        <a href="#" class="courses__item-tag-three">Category</a>
                    </div>
                    <div class="courses__item-content-seven">
                        <ul class="courses__item-meta list-wrap">
                        <li class="price">$99.00</li>
                        <li class="courses__wishlist">
                            <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList">
                                <i class="far fa-heart"></i>
                            </a>
                        </li>
                        </ul>
                        <h2 class="title"><a href="#">Course Title</a></h2>
                        <div class="courses__review">
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span>(5 Reviews)</span>
                        </div>
                    </div>
                    <div class="courses__item-bottom-three courses__item-bottom-five">
                        <ul class="list-wrap">
                            <li><i class="flaticon-book"></i>Lessons 21</li>
                            <li><i class="skillgro-group"></i>Students 999</li>
                        </ul>
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
                attributes: { class: "home_business courses-area-six grey-bg-two" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                ],

                // Default values
                sub_title: '10,000+ unique online courses',
                title: "Our Most Popular Courses",
                btnText: 'See All Courses',
                script: function () {
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
                wrapper.on('change:sub_title change:title change:btnText', () => {
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
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title bold">${this.get('title')}</h2>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-three -->
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
                        <div class="discover-courses-btn text-center mt-30">
                            <a href="{{ route('courses') }}" class="btn arrow-btn">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="" class="injectable"></a>
                        </div>
                    </div>
                `;
                this.components(html);
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
