import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/h4_hero_bg.webp';
import bannerImg from '@images/others/h4_hero_img.webp';
import bannerShape from '@images/others/h4_hero_img_shape01.svg';
import bannerBg from '@images/others/h4_hero_img_shape02.svg';
import blogManagerImg from '@images/sections/hero-four.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "banner-area banner-bg-four tg-motion-effects" },
                traits: [
                    { name: 'title_one', label: 'Title One', type: 'text', changeProp: true },
                    { name: 'title_two', label: 'Title Two', type: 'textarea', changeProp: true },
                    { name: 'title_three', label: 'Title Three', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'contact_title', label: 'Contact Title', type: 'text', changeProp: true },
                    { name: 'contact_number', label: 'Contact Number', type: 'text', changeProp: true },
                    { name: 'contact_icon', label: 'Contact Icon', type: 'image-upload', changeProp: true },
                    { name: 'banner_img', label: 'Banner Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_shape_img', label: 'Banner Shape Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_bg_img', label: 'Banner Background Image', type: 'image-upload', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                title_one: 'Hi, Im',
                title_two: '<span class="highlight">Jecov Rossy</span>',
                title_three: 'Personal Yoga Coach',
                description: "I’m here to help you find the confidence you need to feel amazing in your body!when an unknown printer took a galley of type and scrambled it ecimen has survived not onlyled it to make.",
                btnLink: '/courses',
                btnText: 'Find Your Course',
                contact_title: 'Booking Open',
                contact_number: '+1 (123) 909090',
                contact_icon: `${baseUrl}/frontend/img/icons/phone.svg`,
                banner_img: bannerImg,
                banner_shape_img: bannerShape,
                banner_bg_img: bannerBg,
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
                wrapper.on('change:title_one change:title_two change:title_three change:description change:btnLink change:btnText change:contact_title change:contact_number change:contact_icon change:banner_img change:banner_shape_img change:banner_bg_img change:background_img',
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
                    <div class="row justify-content-center align-items-start">
                        <div class="col-xl-5 col-lg-6">
                            <div class="banner__content-four">
                                <h6 class="sub-title" data-aos="fade-down" data-aos-delay="600">${this.get('title_one')}</h6>
                                <h2 class="title" data-aos="fade-down" data-aos-delay="400">${this.get('title_two')}</h2>
                                <span class="sub-title-two" data-aos="fade-down"
                                    data-aos-delay="200">${this.get('title_three')}</span>
                                <p data-aos="fade-up" data-aos-delay="400">${this.get('description')}</p>
                                <div class="banner__btn-wrap-two" data-aos="fade-up" data-aos-delay="600">
                                        <a href="${this.get('btnLink')}"
                                            class="btn arrow-btn">${this.get('btnText')} <img
                                                src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}"
                                                class="injectable"></a>
                                        <div class="banner__contact">
                                            <div class="icon">
                                                <img src="${this.get('contact_icon')}" alt="${this.get('contact_title')}"
                                                    class="injectable">
                                            </div>
                                            <div class="content">
                                                <span>${this.get('contact_title')}</span>
                                                <a href="${this.get('contact_number')}">${this.get('contact_number')}</a>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-7 col-lg-6 col-md-9 col-sm-10">
                            <div class="banner__images-four">
                                <img src="${this.get('banner_img')}" alt="img" class="main-img">
                                <div class="shape big-shape" data-aos="fade-up" data-aos-delay="900">
                                    <img src="${this.get('banner_shape_img')}" alt="shape">
                                </div>
                                <div class="shape big-shape-two" data-aos="zoom-in" data-aos-delay="700">
                                    <img src="${this.get('banner_bg_img')}" alt="shape"
                                        class="tg-motion-effects5">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="banner__shape-wrap">
                    <img src="${baseUrl}/frontend/img/others/h4_hero_shape01.svg" alt="shape" data-aos="fade-down-right"
                        data-aos-delay="1000">
                    <img src="${baseUrl}/frontend/img/others/h4_hero_shape02.svg" alt="shape" data-aos="fade-up-left"
                        data-aos-delay="1000">
                    <img src="${baseUrl}/frontend/img/others/h4_hero_shape03.svg" alt="shape" class="tg-motion-effects5">
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
