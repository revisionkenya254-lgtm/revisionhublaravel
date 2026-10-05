import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/placeholder.jpg';
import backgroundImg from '@images/others/h4_courses_bg.webp';
import blogManagerImg from '@images/sections/course-four.webp';

const generateCourseHTML = () => {
    let html = '';
    for (let i = 0; i < 4; i++) {
        html += `
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="courses__item-five shine__animate-item">
                    <div class="courses__item-thumb-four shine__animate-link">
                        <a href="#"><img src="${placeholderImage}" alt="img"></a>

                        <span class="courses__price-two">$99.00</span>
                        <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two" >
                            <i class="far fa-heart"></i>
                        </a>
                    </div>
                    <div class="courses__item-content-four">
                        <ul class="courses__item-meta list-wrap">
                            <li class="courses__item-tag courses__item-tag-two">
                                <a
                                    href="#">Category</a>
                            </li>
                            <li class="avg-rating"><i class="fas fa-star"></i>
                                (5 Reviews)
                            </li>
                        </ul>
                        <h2 class="title"><a href="#">Course Title</a>
                        </h2>
                        <div class="courses__item-bottom-three">
                            <ul class="list-wrap">
                                <li><i class="flaticon-book"></i>Lessons 21</li>
                                <li><i class="flaticon-mortarboard"></i>Students 999</li>
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
        <div class="row gutter-24 justify-content-center">
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
                attributes: { class: "courses-area-three youga_course_area section-pt-140 section-pb-110 courses__bg-two" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],

                // Default values
                sub_title: 'Top Class Courses',
                title: "Explore Our World's Best Courses",
                btnText: 'Discover All Class',
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    SVGInject(document.querySelectorAll("img.injectable"));
                }
            },

            init() {
                const wrapper = this;
                // Store swiper instance reference
                this.swiperInstance = null;
                wrapper.on('change:sub_title change:title change:btnText change:background_img', () => {
                    wrapper.updateContent();
                });
                wrapper.updateContent();
            },
            updateContent() {
                const html = `
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-xxl-8 col-lg-10 col-md-12">
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title">${this.get('title')}</h2>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-four -->
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
                        <div class="discover-courses-btn-two text-center mt-30">
                            <a href="{{ route('courses') }}" class="btn arrow-btn btn-four">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
                        </div>
                    </div>
                    <div class="courses__shape-wrap-two">
                        <img src="${baseUrl}/frontend/img/others/h4_course_shape.svg" alt="shape" class="rotateme">
                        <img src="${baseUrl}/frontend/img/others/h4_course_shape.svg" alt="shape" class="rotateme">
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
