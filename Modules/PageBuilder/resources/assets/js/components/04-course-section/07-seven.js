import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/placeholder.jpg';
import backgroundImg from '@images/others/h5_courses_bg.webp';
import blogManagerImg from '@images/sections/course-seven.webp';

const generateCourseHTML = () => {
    let html = '';
    for (let i = 0; i < 4; i++) {
        html += `
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="courses__item-six shine__animate-item">
                    <div class="courses__item-thumb-five shine__animate-link">
                        <a href="#"><img
                                src="${placeholderImage}" alt="Course Title"></a>
                        <a href="javascript:;"
                            class="wsus-wishlist-btn common-white courses__wishlist-two" aria-label="WishList">
                            <i class="far fa-heart"></i>
                        </a>
                    </div>
                    <div class="courses__item-content-five">
                        <ul class="courses__item-meta list-wrap">
                            <li class="courses__review courses__review-two">
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                </div>
                                <span>(5)</span>
                            </li>
                                <li class="price">$99.00</li>
                        </ul>
                        <h2 class="title"><a
                                href="#">Course Title</a>
                        </h2>
                        <p>Artificial Intelligence and Machine</p>
                        <div class="courses__item-content-bottom-two">
                            <ul class="list-wrap">
                                <li>
                                    <div class="icon">
                                        <i class="flaticon-book"></i>
                                    </div>
                                    <p><span>Lessons 21</p>
                                </li>
                                <li>
                                    <div class="icon">
                                        <i class="flaticon-mortarboard"></i>
                                    </div>
                                    <p><span>Students 999</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    return html;
};

const renderTabPane = (id, title, active = false) => `
    <div class="tab-pane fade ${active ? 'show active' : ''}" id="${id}-tab-pane" role="tabpanel" aria-labelledby="${id}-tab" tabindex="0">
        <div class="row justify-content-center">
            ${generateCourseHTML()}
        </div>
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
                attributes: { class: "courses-area-four courses__bg-three" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],

                // Default values
                sub_title: 'Top Class Courses',
                title: "Explore Our World's Best Courses",
                description: "Check out the most demanding courses right now",
                background_img: backgroundImg,
                script:function(){
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
                // Store swiper instance reference
                this.swiperInstance = null;
                wrapper.on('change:sub_title change:title change:description change:background_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                wrapper.updateContent();
                wrapper.bgUpdate();
                this.applyRules(this, true);
            },
            updateContent() {
                const html = `
                    <div class="courses__bg-shape-one">
                        <img src="${baseUrl}/frontend/img/courses/h5_courses_bg_shape01.svg" class="injectable">
                    </div>
                    <div class="courses__bg-shape-two">
                        <img src="${baseUrl}/frontend/img/courses/h5_courses_bg_shape02.svg" class="injectable">
                    </div>
                    <div class="container home_kindergarten">
                        <div class="row justify-content-center">
                            <div class="col-lg-9 col-xl-8 col-xxl-6">
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title bold">${this.get('title')}</h2>
                                    <p class="desc">${this.get('description')}</p>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-seven -->
                            <div class="row justify-content-center mb-50">
                                <div class="courses__nav-two">
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
                    <div class="courses__shape-wrap-three">
                        <img src="${baseUrl}/frontend/img/courses/h5_courses_shape01.svg" alt="shape" data-aos="fade-right"
                            data-aos-delay="400">
                        <img src="${baseUrl}/frontend/img/courses/h5_courses_shape02.svg" alt="shape"
                            data-aos="fade-up-right" data-aos-delay="400">
                        <img src="${baseUrl}/frontend/img/courses/h5_courses_shape03.svg" alt="shape"
                            class="alltuchtopdown">
                        <img src="${baseUrl}/frontend/img/courses/h5_courses_shape04.svg" alt="shape"
                            data-aos="fade-up-left" data-aos-delay="400">
                    </div>
                `;
                this.components(html);
                this.applyRules(this, true);
            },
            bgUpdate() {
                const bg = this.get('background_img') || '';
                this.addAttributes({ 'data-background': bg });
                this.addStyle({ 'background-image': `url('${bg}')` });
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
