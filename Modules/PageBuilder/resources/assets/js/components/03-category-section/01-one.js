import { registerComponent } from '@utils/registerComponent';
import blogManagerImg from '@images/sections/trending-category.webp';

const categoryData = [
    { icon: "flaticon-graphic-design", name: "Graphic Design", totalCourse: 22 },
    { icon: "flaticon-investment", name: "Finance", totalCourse: 41 },
    { icon: "flaticon-coding", name: "Development", totalCourse: 29 },
    { icon: "flaticon-email", name: "Marketing", totalCourse: 31 },
    { icon: "flaticon-fashion", name: "Life Style", totalCourse: 23 },
    { icon: "flaticon-interaction", name: "Management", totalCourse: 19 },
    { icon: "flaticon-web-design", name: "App Design", totalCourse: 18 },
];

// Generate the brand items HTML string by mapping image names
const categoryHtml = categoryData.map(category => `
<div class="swiper-slide">
    <div class="categories__item">
        <a href="#">
        <div class="icon">
            <i class="${category.icon}"></i>
        </div>
        <span class="name">${category.name}</span>
        <span class="courses">(${category.totalCourse})</span>
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
                attributes: { class: "categories-area section-py-120" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                ],

                // Default values
                sub_title: 'Trending Categories',
                title: 'Top Category We Have',
                description: 'when known printer took a galley of type scrambl edmake',

                script: function () {
                    var categoriesSwiper = new Swiper(".categories-active", {
                        // Optional parameters
                        slidesPerView: 6,
                        spaceBetween: 44,
                        loop: true,
                        breakpoints: {
                            1500: {
                                slidesPerView: 6,
                            },
                            1200: {
                                slidesPerView: 5,
                            },
                            992: {
                                slidesPerView: 4,
                                spaceBetween: 30,
                            },
                            768: {
                                slidesPerView: 3,
                                spaceBetween: 25,
                            },
                            576: {
                                slidesPerView: 2,
                            },
                            0: {
                                slidesPerView: 2,
                                spaceBetween: 20,
                            },
                        },
                        // Navigation arrows
                        navigation: {
                            nextEl: ".categories-button-next",
                            prevEl: ".categories-button-prev",
                        },
                    });
                },
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title change:description', () => {
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
                            <div class="col-xl-5 col-lg-7">
                                <div class="section__title text-center mb-40">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title">${this.get('title')}</h2>
                                    <p class="desc">${this.get('description')}</p>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:trending-category -->
                        <div class="row">
                            <div class="col-12">
                                <div class="categories__wrap">
                                    <div class="swiper categories-active">
                                        <div class="swiper-wrapper">
                                            ${categoryHtml}
                                        </div>
                                    </div>
                                    <div class="categories__nav">
                                        <button class="categories-button-prev">
                                            <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M15 7L1 7M1 7L7 1M1 7L7 13" stroke="#161439" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                        <button class="categories-button-next">
                                            <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M1 7L15 7M15 7L9 1M15 7L9 13" stroke="#161439" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_END -->
                    </div>
                `;
                // Update the components
                this.components(html);
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
