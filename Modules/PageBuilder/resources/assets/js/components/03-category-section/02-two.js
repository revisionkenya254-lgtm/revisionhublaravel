import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/categories_bg.webp';
import blogManagerImg from '@images/sections/category-two.webp';

const categoryData = [
    { icon: "flaticon-graphic-design", name: "Graphic Design", totalCourse: 22 },
    { icon: "flaticon-investment", name: "Finance", totalCourse: 41 },
    { icon: "flaticon-coding", name: "Development", totalCourse: 29 },
    { icon: "flaticon-web-design", name: "App Design", totalCourse: 18 },
];

// Generate the brand items HTML string by mapping image names
const categoryHtml = categoryData.map(category => `
<div class="col-lg-3 col-sm-6">
    <div class="categories__item-three">
        <a href="#">
            <div class="icon">
                <i class="${category.icon}"></i>
            </div>
            <span class="name">${category.name}</span>
            <span class="courses">${category.totalCourse} Courses</span>
        </a>
    </div>
</div>
`).join('');

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "categories-area-three fix section-pt-140 section-pb-110 categories__bg" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],

                // Default values
                sub_title: 'Our Top Categories',
                title: 'Your Creative and Passionate Business Coach',
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
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
                wrapper.on('change:sub_title change:title change:background_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title bold">${this.get('title')}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                         <!-- DYNAMIC_PART_START:category-two -->
                                ${categoryHtml}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="categories__shape-wrap">
                    <img src="${baseUrl}/frontend/img/others/h7_categories_shape01.svg" alt="shape" class="rotateme">
                    <img src="${baseUrl}/frontend/img/others/h7_categories_shape02.svg" alt="shape" data-aos="fade-down-left"
                        data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/h7_categories_shape03.svg" alt="shape" class="alltuchtopdown">
                    <img src="${baseUrl}/frontend/img/others/h7_categories_shape04.svg" alt="shape" data-aos="fade-up-right"
                        data-aos-delay="400">
                </div>
                `;
                // Update the components
                this.components(html);
                // Apply rules recursively
                this.applyRules(this, true);
                this.addAttributes({ 'data-background': this.get('background_img') || '' });
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
