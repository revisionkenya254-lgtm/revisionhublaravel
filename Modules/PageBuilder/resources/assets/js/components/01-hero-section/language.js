import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/h6_hero_bg.webp';
import bannerImg from '@images/others/h6_hero_img.webp';
import studentGrp from '@images/others/student_grp.png';
import courseImg from '@images/icons/course_icon03.svg';
import blogManagerImg from '@images/sections/hero-six.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "banner-area banner-bg-six tg-motion-effects" },
                traits: [
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'videoLink', label: 'Video Link', type: 'text', changeProp: true },
                    { name: 'videoBtnText', label: 'Video Button Text', type: 'text', changeProp: true },
                    { name: 'feature_title', label: 'Feature Title', type: 'text', changeProp: true },
                    { name: 'feature_icon', label: 'Feature Image', type: 'image-upload', changeProp: true },
                    { name: 'feature_two', label: 'Feature Two', type: 'text', changeProp: true },
                    { name: 'feature_two_title', label: 'Feature Two Title', type: 'text', changeProp: true },
                    { name: 'feature_two_icon', label: 'Feature Two Image', type: 'image-upload', changeProp: true },
                    { name: 'feature_three', label: 'Feature Two', type: 'text', changeProp: true },
                    { name: 'feature_three_title', label: 'Feature Two Title', type: 'text', changeProp: true },
                    { name: 'feature_three_icon', label: 'Feature Two Image', type: 'image-upload', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                    { name: 'banner_img', label: 'Banner Image', type: 'image-upload', changeProp: true },
                ],
                title: 'Confidently speak a new language',
                description: "<p>More than 240 thousand new users SignUp Here!</p><ul><li>Live classes online 24/7</li><li>Learn in small groups or 1-on-1</li><li>Free 7-day trial</li></ul>",
                btnLink: `${baseUrl}/courses`,
                btnText: 'Find Your Course',
                videoLink: 'https://www.youtube.com/watch?v=pMzGDBP6Bic',
                videoBtnText: 'Watch Our Class Demo',
                feature_title: '80k Enrolled Students',
                feature_icon: studentGrp,
                feature_two: '4.9/5',
                feature_two_title: 'Real Reviews',
                feature_two_icon: `${baseUrl}/frontend/img/icons/star.svg`,
                feature_three: '45+',
                feature_three_title: 'Courses',
                feature_three_icon: courseImg,
                banner_img: bannerImg,
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    var $svgIconBox = $('.tg-svg');
                    $svgIconBox.each(function () {
                        var $this = $(this),
                            $svgIcon = $this.find('.svg-icon'),
                            $id = $svgIcon.attr('id'),
                            $icon = $svgIcon.data('svg-icon');
                        if ($id) {
                            var $vivus = new Vivus($id, {
                                duration: 80,
                                file: $icon,
                            });
                            $this.on('mouseenter', function () {
                                $vivus.reset().play();
                            });
                        }
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
                wrapper.on('change:title change:description change:btnLink change:btnText change:videoLink change:videoBtnText change:feature_title change:feature_two change:feature_two_title change:feature_three change:feature_three_title change:banner_img change:background_img change:feature_icon change:feature_two_icon change:feature_three_icon',
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
                    <div class="home_language container">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-lg-7 col-md-9 col-sm-10 order-0 order-lg-2">
                                <div class="banner__images-six">
                                    <div class="main-img tg-svg">
                                        <img src="${this.get('banner_img')}" alt="${this.get('title')}">
                                        <span class="svg-icon" id="banner-svg" data-svg-icon="${baseUrl}/frontend/img/banner/h6_banner_img_shape03.svg"></span>
                                    </div>
                                    <div class="about__enrolled about__enrolled-two" data-aos="fade-right" data-aos-delay="1000">
                                        <img src="${this.get('feature_icon')}" alt="${this.get('feature_title')}">
                                        <p class="title">${this.get('feature_title')}</p>
                                    </div>
                                    <div class="banner__review" data-aos="fade-left" data-aos-delay="1000">
                                        <div class="icon">
                                            <img src="${this.get('feature_two_icon')}" alt="${this.get('feature_two_title')}" class="injectable">
                                        </div>
                                        <h6 class="title">${this.get('feature_two')} <span> ${this.get('feature_two_title')}</span></h6>
                                    </div>
                                    <div class="banner__courses" data-aos="fade-up" data-aos-delay="1000">
                                        <div class="icon">
                                            <img src="${this.get('feature_three_icon')}" alt="${this.get('feature_three_title')}" class="injectable">
                                        </div>
                                        <h6 class="title">${this.get('feature_three')} <span>${this.get('feature_three_title')}</span></h6>
                                    </div>
                                    <div class="shape-wrap">
                                        <img src="${baseUrl}/frontend/img/banner/h6_banner_img_shape01.svg" alt="shape">
                                        <img src="${baseUrl}/frontend/img/banner/h6_banner_img_shape02.svg" alt="shape" class="alltuchtopdown">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="banner__content-six">
                                    <h2 class="title" data-aos="fade-up" data-aos-delay="200">${this.get('title')}</h2>

                                    <div class="sub-title wsus_content-box" data-aos="fade-up" data-aos-delay="400">
                                        ${this.get('description')}
                                    </div>
                                    <div class="banner__btn-wrap-three" data-aos="fade-up" data-aos-delay="800">
                                        <a href="${this.get('btnLink')}" class="btn arrow-btn btn-four">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="img" class="injectable"></a>
                                        <a href="${this.get('videoLink')}" class="play-btn popup-video" aria-label="${this.get('videoBtnText')}"><i class="fas fa-play"></i> ${this.get('videoBtnText')}</a>
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
