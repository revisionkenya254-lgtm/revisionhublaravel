import { registerComponent } from '@utils/registerComponent';
import icon from '@images/icons/h4_features_icon03.svg';
import blogManagerImg from '@images/sections/category-three.webp';

const categoryData = [
    { name: "Graphic Design", totalCourse: 22 },
    { name: "Finance", totalCourse: 41 },
    { name: "Development", totalCourse: 29 },
    { name: "App Development", totalCourse: 19 },
    { name: "Marketing", totalCourse: 31 },
    { name: "Life Style", totalCourse: 23 },
    { name: "Management", totalCourse: 19 },
    { name: "App Design", totalCourse: 18 },
];

// Generate the brand items HTML string by mapping image names
const categoryHtml = categoryData.map(category => `
<div class="col-lg-3 col-sm-6">
    <div class="categories__item-four shine__animate-item">
        <a href="#" class="shine__animate-link">
            <img src="${icon}" alt="${category.name}">
            <span class="name">${category.name} <strong>(${category.totalCourse})</strong></span>
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
                attributes: { class: "categories-area-four fix section-pt-140 section-pb-110" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                ],

                // Default values
                sub_title: 'Our Top Categories',
                title: 'Our Food categories',
                script: function () {
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
                wrapper.on('change:sub_title change:title', () => {
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
                        <!-- DYNAMIC_PART_START:category-three -->
                                ${categoryHtml}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="categories__shape-wrap-two">
                    <img src="${baseUrl}/frontend/img/others/cat_shape01.svg" alt="shape" data-aos="fade-down-right"
                        data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/cat_shape02.svg" alt="shape" data-aos="fade-up-left"
                        data-aos-delay="400">
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
