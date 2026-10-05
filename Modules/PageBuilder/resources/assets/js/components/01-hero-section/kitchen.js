import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/h8_hero_bg.webp';
import bannerImg from '@images/others/h8_hero_img.webp';
import bannerShape from '@images/others/h8_hero_img_shape02.svg';
import bannerBg from '@images/others/h8_hero_img_shape.svg';
import studentGrp from '@images/others/student_grp.png';
import brand1 from '@images/brand/brand01.png';
import brand2 from '@images/brand/brand02.png';
import brand3 from '@images/brand/brand03.png';
import brand4 from '@images/brand/brand04.png';
import brand5 from '@images/brand/brand05.png';
import brand6 from '@images/brand/brand06.png';
import brand7 from '@images/brand/brand07.png';
import blogManagerImg from '@images/sections/hero-five.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "banner-area fix banner-bg-seven tg-motion-effects" },
                traits: [
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'placeholderText', label: 'Input placeholder text', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'count', label: 'Count', type: 'text', changeProp: true },
                    { name: 'count_title', label: 'Count Title', type: 'text', changeProp: true },
                    { name: 'count_img', label: 'Count Image', type: 'image-upload', changeProp: true },
                    { name: 'count_two', label: 'Count Two', type: 'text', changeProp: true },
                    { name: 'count_two_title', label: 'Count Two Title', type: 'text', changeProp: true },
                    { name: 'banner_img', label: 'Banner Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_shape_img', label: 'Banner Shape Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_bg_img', label: 'Banner Background Image', type: 'image-upload', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                    {
                        type: 'accordion',
                        label: 'Brands',
                        name: 'brands',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Brand" },
                            { name: 'logo', type: 'image-upload', label: 'Logo', default: brand1 },
                            { name: 'link', type: 'text', label: 'Link', default: '#' }
                        ]
                    }
                ],
                title: 'Take Your <span class="highlight">Cooking</span> Skill <br> To The Next Level',
                description: "Free & Premium online courses from the world’s Join 17 million learners today.",
                placeholderText: 'Search Here . . .',
                btnText: 'Find Courses',
                count: '80K',
                count_title: 'Enrolled Students',
                count_img: studentGrp,
                count_two: '12k+',
                count_two_title: 'Total Recipes',
                banner_img: bannerImg,
                banner_shape_img: bannerShape,
                banner_bg_img: bannerBg,
                background_img: backgroundImg,
                brands: [
                    { title: 'Brand 1', logo: brand1, link: "#" },
                    { title: 'Brand 2', logo: brand2, link: "#" },
                    { title: 'Brand 3', logo: brand3, link: "#" },
                    { title: 'Brand 4', logo: brand4, link: "#" },
                    { title: 'Brand 5', logo: brand5, link: "#" },
                    { title: 'Brand 6', logo: brand6, link: "#" },
                    { title: 'Brand 7', logo: brand7, link: "#" },
                ],
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    SVGInject(document.querySelectorAll("img.injectable"));

                    var brandSwiper = new Swiper('.brand-swiper-active-two', {
                        // Optional parameters
                        slidesPerView: 5,
                        spaceBetween: 30,
                        observer: true,
                        observeParents: true,
                        loop: true,
                        breakpoints: {
                            '1500': {
                                slidesPerView: 5,
                            },
                            '1200': {
                                slidesPerView: 4,
                            },
                            '992': {
                                slidesPerView: 4,
                                spaceBetween: 24,
                            },
                            '768': {
                                slidesPerView: 4,
                                spaceBetween: 24,
                            },
                            '576': {
                                slidesPerView: 3,
                            },
                            '0': {
                                slidesPerView: 2,
                            },
                        },
                    });
                    aosAnimation();
                    function aosAnimation() {
                        AOS.init({
                            duration: 1000,
                            mirror: true,
                            once: true,
                            disable: 'mobile',
                        });
                    }
                },
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:description change:placeholderText change:btnText change:count change:count_title change:count_two change:count_two_title change:count_img change:banner_img change:banner_shape_img change:banner_bg_img change:background_img change:brands',
                    () => {
                        wrapper.updateContent();
                        wrapper.view.render();
                    }
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const brandItems = (this.get('brands') || []).map(brand => `
                    <div class="swiper-slide">
                        <div class="brand__item-two">
                            <a href="${brand.link}"><img src="${brand.logo}" alt="${brand.title}"></a>
                        </div>
                    </div>
                `).join('');

                const html = `
                <div class="container">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6">
                            <div class="banner__content-seven">
                                <h2 class="title">${this.get('title')}</h2>
                                <p>${this.get('description')}</p>
                                <div class="slider__search banner__search">
                                    <!-- DYNAMIC_PART_START:search-form placeholder='${this.get('placeholderText')}' buttonText='${this.get('btnText')}' -->
                                        <form action="#" class="slider__search-form">
                                            <input type="text" name="search"
                                                placeholder="${this.get('placeholderText')}">
                                            <button type="button">${this.get('btnText')}</button>
                                        </form>
                                    <!-- DYNAMIC_PART_END -->
                                </div>
                                <div class="banner__brand-wrap">
                                    <div class="swiper-container brand-swiper-active-two">
                                        <div class="swiper-wrapper">
                                            ${brandItems}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-10">
                            <div class="banner__images-seven">
                                <img src="${this.get('banner_img')}" alt="background image" class="main-img">
                                <div class="big_shape">
                                    <img src="${this.get('banner_shape_img')}" alt="background image"
                                        class="injectable tg-motion-effects1">
                                </div>
                                <div class="shape">
                                    <img src="${this.get('banner_bg_img')}" alt="background image"
                                        class="rotateme">
                                </div>
                                <div class="about__enrolled" data-aos="fade-right" data-aos-delay="200">
                                    <p class="title"><span>${this.get('count')}</span>
                                        ${this.get('count_title')}}</p>
                                    <img src="${this.get('count_img')}" alt="${this.get('count_title')}}">
                                </div>
                                <div class="banner__all-recipe" data-aos="fade-left" data-aos-delay="200">
                                    <h2 class="count">${this.get('count_two')}</h2>
                                    <span>${this.get('count_two_title')}</span>
                                </div>
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
