import { registerComponent } from '@utils/registerComponent';
import sliderImgOne from '@images/others/theme_business_slider_1.webp';
import sliderImgTwo from '@images/others/theme_business_slider_2.webp';
import mediaImage from '@images/sections/slider.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "slider__area" },
                traits: [
                    {
                        type: 'accordion',
                        label: 'Sliders',
                        name: 'sliders',
                        changeProp: true,
                        inputsConfig: [
                            { name: 'title', label: 'Title', type: 'text', changeProp: true, default: "Professional Courses" },
                            { name: 'sub_title', label: 'Sub Title', type: 'textarea', changeProp: true, default: 'Find Business <span class="highlight">Courses</span> & Develop Your Skills' },
                            { name: 'description', label: 'Description', type: 'textarea', changeProp: true, default: "Free & Premium online courses from the world’s Join 17 million learners today." },
                            { name: 'placeholderText', label: 'Input placeholder text', type: 'text', changeProp: true, default: "Search Here . . ." },
                            { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true, default: "Find Courses" },
                            { name: 'Image', label: 'Slider Image', type: 'image-upload', changeProp: true, default: sliderImgOne },
                        ]
                    },
                ],
                sliders: [
                    { title: 'Professional Courses', sub_title: 'Find Business <span class="highlight">Courses</span> & Develop Your Skills', description: "Free & Premium online courses from the world’s Join 17 million learners today.", placeholderText: "Search Here . . .", btnText: "Find Courses", Image: sliderImgOne },
                    { title: 'Professional Courses', sub_title: 'Find Business <span class="highlight">Courses</span> & Develop Your Skills', description: "Free & Premium online courses from the world’s Join 17 million learners today.", placeholderText: "Search Here . . .", btnText: "Find Courses", Image: sliderImgTwo },

                ],
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    var swiper2 = new Swiper(".slider__active", {
                        spaceBetween: 0,
                        effect: "fade",
                        loop: true,
                        autoplay: {
                            delay: 6000,
                        },
                    });
                },
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sliders', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const generateHtml = (this.get('sliders') || []).map(slider => `
                    <div class="swiper-slide slider__single">
                        <div class="slider__bg" data-background="${slider.Image}">
                            <div class="container">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-7">
                                        <div class="slider__content">
                                            <span class="sub-title">${slider.title}</span>
                                            <h2 class="title">${slider.sub_title}</h2>
                                            <p>${slider.description}</p>
                                            <div class="slider__search">
                                                <!-- DYNAMIC_PART_START:search-form placeholder='${slider.placeholderText}' buttonText='${slider.btnText}' -->
                                                    <form action="#" class="slider__search-form">
                                                        <input type="text" name="search"
                                                            placeholder="${slider.placeholderText}">
                                                        <button type="button">${slider.btnText}</button>
                                                    </form>
                                                <!-- DYNAMIC_PART_END -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');

                const html = `
                    <div class="swiper-container slider__active">
                        <div class="swiper-wrapper">
                            ${generateHtml}
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