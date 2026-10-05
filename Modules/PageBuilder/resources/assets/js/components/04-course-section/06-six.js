import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/placeholder.jpg';
import backgroundImg from '@images/others/h6_courses_bg.webp';
import blogManagerImg from '@images/sections/course-six.webp';

const generateCourseHTML = () => {
    let html = '';
    for (let i = 0; i < 4; i++) {
        html += `
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="courses__item-seven">
                    <div class="courses__item-thumb-six">
                        <a href="#"><img
                                src="${placeholderImage}" alt="Course Title"></a>
                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"
                            aria-label="WishList">
                            <i class="far fa-heart"></i>
                        </a>
                        <a href="#"
                            class="courses__item-tag-three">Category</a>
                        <div class="courses__review">
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span>(5)</span>
                        </div>
                    </div>
                    <div class="courses__item-content-six">
                        <div class="courses__item-content-six-top">
                            <h2 class="title"><a
                                    href="#">John doe</a>
                            </h2>
                                <h3 class="price">$99.00</h3>
                        </div>
                        <span>Professional Tutor</span>
                        <ul class="courses__item-meta-two list-wrap">
                                <li>English <img
                                        src="${baseUrl}/frontend/img/icons/graph.svg" alt=""
                                        class="injectable"></li>
                        </ul>
                        <p>Course Title</p>
                    </div>
                    <div class="courses__item-bottom-three courses__item-bottom-four">
                        <ul class="list-wrap">
                            <li><i class="flaticon-book"></i>Lessons 21</li>
                            <li><i class="flaticon-mortarboard"></i>Students 999</li>
                        </ul>
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
                attributes: { class: "courses-area-five section-py-140 courses__bg-four" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],

                // Default values
                sub_title: 'Top Class Courses',
                title: "Explore Our World's Best Courses",
                description: "Check out the most demanding courses right now",
                btnText: 'See All Courses',
                background_img: backgroundImg,
                script:function(){
                    SVGInject(document.querySelectorAll("img.injectable"));
                }
            },

            init() {
                const wrapper = this;
                // Store swiper instance reference
                this.swiperInstance = null;
                wrapper.on('change:sub_title change:title change:description change:btnText change:background_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                wrapper.updateContent();
                wrapper.bgUpdate();
                this.applyRules(this, true);
            },
            updateContent() {
                const html = `
                    <div class="container home_language">
                        <div class="row justify-content-center">
                            <div class="col-lg-9 col-xl-8 col-xxl-6">
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title bold">${this.get('title')}</h2>
                                    <p class="desc">${this.get('description')}</p>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-six -->
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
                        <div class="view-all-btn view-all-categories">
                            <a href="{{ route('courses') }}"><span>${this.get('btnText')}</span><img
                                    src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="" class="injectable"></a>
                        </div>
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
