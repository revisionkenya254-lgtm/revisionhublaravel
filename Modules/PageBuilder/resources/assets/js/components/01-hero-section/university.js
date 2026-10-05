import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/theme_university_hero_bg.webp';
import bannerImg from '@images/others/theme_university_banner_img.webp';
import bannerBg from '@images/others/theme_university_banner_bg.svg';
import studentGrp from '@images/others/student_grp.png';
import blogManagerImg from '@images/sections/hero-three.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "banner-area banner-bg-three tg-motion-effects" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'count', label: 'Count', type: 'text', changeProp: true },
                    { name: 'count_title', label: 'Count Title', type: 'text', changeProp: true },
                    { name: 'count_img', label: 'Count Image', type: 'image-upload', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_img', label: 'Banner Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_shape_img', label: 'Banner Background Image', type: 'image-upload', changeProp: true },
                ],
                sub_title: 'FOR A BETTER FUTURE',
                title: 'Empowering Minds Inspiring <span>Futures</span>',
                description: "Every teaching and learning journey is unique Follwoing We'll help guide your way.",
                btnLink: '/courses',
                btnText: 'Find Your Course',
                count: '80K',
                count_title: 'Enrolled Students',
                count_img: studentGrp,
                banner_img: bannerImg,
                banner_shape_img: bannerBg,
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
                wrapper.on('change:sub_title change:title change:description change:btnLink change:btnText change:count change:count_title change:banner_img change:banner_shape_img change:background_img change:count_img',
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
                    <div class="container">
                        <div class="row justify-content-between align-items-start">
                            <div class="col-xl-5 col-lg-6">
                                <div class="banner__content-three">
                                    <span class="sub-title" data-aos="fade-right"
                                        data-aos-delay="200">${this.get('sub_title')}</span>
                                    <h2 class="title" data-aos="fade-right" data-aos-delay="400">${this.get('title')}</h2>
                                    <p data-aos="fade-right" data-aos-delay="600">${this.get('description')}</p>
                                        <div class="banner__btn-wrap" data-aos="fade-right" data-aos-delay="800">
                                            <a href="${this.get('btnLink')}"
                                                class="btn arrow-btn">${this.get('btnText')} <img
                                                    src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="img"
                                                    class="injectable"></a>
                                </div>
                                <div class="shape">
                                    <img src="${baseUrl}/frontend/img/banner/h3_hero_shape01.svg" alt="shape" data-aos="fade-right" data-aos-delay="1000">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="banner__images-three">
                                <img src="${this.get('banner_img')}" alt="img" class="main-img">
                                <div class="shape big-shape" data-aos="fade-up" data-aos-delay="800">
                                    <img src="${this.get('banner_shape_img')}" alt="shape" class="tg-motion-effects1">
                                </div>
                                <div class="shape__wrap">
                                    <img src="${baseUrl}/frontend/img/banner/h3_hero_shape02.svg" alt="shape"
                                        data-aos="fade-down-right" data-aos-delay="400">
                                    <img src="${baseUrl}/frontend/img/banner/h3_hero_shape03.svg" alt="shape"
                                        data-aos="fade-down-left" data-aos-delay="400">
                                </div>
                                <div class="about__enrolled" data-aos="fade-right" data-aos-delay="900">
                                    <p class="title"><span>${this.get('count')}</span>
                                        ${this.get('count_title')}</p>
                                    <img src="${this.get('count_img')}" alt="${this.get('count_title')}">
                                </div>
                            </div>
                        </div>
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
