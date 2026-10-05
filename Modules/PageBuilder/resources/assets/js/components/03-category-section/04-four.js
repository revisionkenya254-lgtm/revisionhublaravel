import { registerComponent } from '@utils/registerComponent';
import icon from '@images/icons/h4_features_icon03.svg';
import blogManagerImg from '@images/sections/category-four.webp';

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
<div class="col-xl-3 col-lg-4 col-sm-6">
    <div class="categories__item-two">
        <a href="#">
            <div class="content">
                <img src="${icon}" alt="${category.name}">
                <span
                    class="name"><strong>${category.name}</strong>${category.totalCourse} Courses</span>
            </div>
            <div class="icon">
                <i class="skillgro-next-2"></i>
            </div>
        </a>
    </div>
</div>
`).join('');

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "categories-area-two section-pt-140 section-pb-110" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                ],

                // Default values
                sub_title: 'Trending Categories',
                title: 'Top Category We Have',
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title', () => {
                    wrapper.updateContent();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container home_language">
                    <div class="row align-items-center">
                        <div class="col-lg-6 col-md-8">
                            <div class="section__title mb-50 mb-xs-20">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title bold">${this.get('title')}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- DYNAMIC_PART_START:category-four -->
                                ${categoryHtml}
                        <!-- DYNAMIC_PART_END -->
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
