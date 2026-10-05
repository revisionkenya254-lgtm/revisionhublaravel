import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/h5_hero_bg.webp';
import bannerImg from '@images/others/h5_hero_img.webp';
import blogManagerImg from '@images/sections/hero-seven.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "banner-area banner-bg-five tg-motion-effects" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_img', label: 'Banner Image', type: 'image-upload', changeProp: true },
                ],
                sub_title: 'Kindergarten Care School',
                title: 'Together We’ll <br> Explore New Things',
                description: "Every teaching and learning journey is unique Following We'll help guide your way.",
                btnLink: `${baseUrl}/courses`,
                btnText: 'Find Your Course',
                banner_img: bannerImg,
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
                wrapper.on('change:sub_title change:title change:description change:btnLink change:btnText change:banner_img change:background_img',
                    () => {
                        wrapper.updateContent();
                        wrapper.view.render();
                    }
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                    <div class="home_kindergarten banner-bg-five-shape" data-background="${baseUrl}/frontend/img/banner/h5_hero_bg_shape.svg"></div>
                        <div class="container">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-xl-5 col-lg-5">
                                    <div class="banner__content-five">
                                        <span class="sub-title" data-aos="fade-right" data-aos-delay="200">${this.get('sub_title')}</span>
                                        <h2 class="title" data-aos="fade-right" data-aos-delay="400">${this.get('title')}</h2>
                                        <p data-aos="fade-right" data-aos-delay="600">${this.get('description')}</p>
                                        <div class="banner__btn" data-aos="fade-right" data-aos-delay="800">
                                            <a href="${this.get('btnLink')}" class="btn arrow-btn">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-7 col-lg-7 col-md-9 col-sm-10">
                                    <div class="banner__images-five">
                                        <img src="${this.get('banner_img')}" alt="${this.get('title')}">
                                        <div class="shape-wrap">
                                            <div class="shape-one" data-aos="fade-up-right" data-aos-delay="800">
                                                <img src="${baseUrl}/frontend/img/banner/h5_hero_shape04.svg" alt="shape" class="tg-motion-effects1">
                                            </div>
                                            <div class="shape-two" data-aos="fade-down-left" data-aos-delay="800">
                                                <img src="${baseUrl}/frontend/img/banner/h5_hero_shape05.svg" alt="shape" class="tg-motion-effects3">
                                            </div>
                                            <div class="shape-three" data-aos="fade-up-left" data-aos-delay="800">
                                                <img src="${baseUrl}/frontend/img/banner/h5_hero_shape06.svg" alt="shape">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="banner__shape-wrap-two">
                            <img src="${baseUrl}/frontend/img/banner/h5_hero_shape01.svg" alt="shape" data-aos="fade-right" data-aos-delay="1000">
                            <img src="${baseUrl}/frontend/img/banner/h5_hero_shape02.svg" alt="shape" class="tg-motion-effects7">
                            <img src="${baseUrl}/frontend/img/banner/h5_hero_shape03.svg" alt="shape" class="tg-motion-effects3">
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
