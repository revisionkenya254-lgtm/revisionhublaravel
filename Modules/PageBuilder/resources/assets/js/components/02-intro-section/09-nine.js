import { registerComponent } from '@utils/registerComponent';
import countImg from '@images/icons/theme_university_features_icon_4.svg';
import aboutImg from '@images/others/h8_about_img01.webp';
import aboutImgTwo from '@images/others/h8_about_img02.webp';
import backgroundImg from '@images/others/h8_about_bg.webp';
import blogManagerImg from '@images/sections/about-seven.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "about-area-six about__bg" },
                traits: [
                    { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'count', label: 'Count', type: 'text', changeProp: true },
                    { name: 'count_title', label: 'Count Title', type: 'text', changeProp: true },
                    { name: 'count_img', label: 'Count Image', type: 'image-upload', changeProp: true },
                    { name: 'about_img', label: 'Image', type: 'image-upload', changeProp: true },
                    { name: 'about_img_two', label: 'Image Two', type: 'image-upload', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                subtitle: 'Get More About Us',
                title: 'Thousand Of Top <span class="highlight">Courses</span><br> Now in One Place',
                description: "<p>Groove’s intuitive shared inbox makes it easy for team members to organize, prioritize and.In this episode of the Smashing Pod we’re talking about Web Platform Baseline.</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>",
                btnLink: `${baseUrl}/about-us`,
                btnText: 'Discover All Class',
                count: '86%',
                count_title: 'Course Success',
                count_img: countImg,
                about_img: aboutImg,
                about_img_two: aboutImgTwo,
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
                wrapper.on('change:subtitle change:title change:description change:btnLink change:btnText change:count change:count_title change:count_img change:about_img change:about_img_two change:background_img',
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
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6 col-md-10">
                            <div class="about__images-six">
                                <img src="${this.get('about_img')}" alt="${this.get('subtitle')}">
                                <img src="${this.get('about_img_two')}" alt="${this.get('subtitle')}" data-aos="fade-left"
                                    data-aos-delay="400">
                                <div class="about__success" data-aos="fade-up" data-aos-delay="400">
                                    <div class="icon">
                                        <img src="${this.get('count_img')}" alt="${this.get('count_title')}">
                                    </div>
                                    <div class="content">
                                        <h2 class="title">${this.get('count')}</h2>
                                        <span>${this.get('count_title')}</span>
                                    </div>
                                </div>
                                <div class="shape">
                                    <img src="${baseUrl}/frontend/img/others/h8_about_img_shape.svg" alt="shape" class="alltuchtopdown">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="about__content-six">
                                <div class="section__title mb-15">
                                    <span class="sub-title">${this.get('subtitle')}</span>
                                    <h2 class="title">${this.get('title')}</h2>
                                </div>
                                <div>${this.get('description')}</div>
                                <a href="${this.get('btnLink')}" class="btn arrow-btn btn-four">${this.get('btnText')} <img
                                        src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about__shape-wrap">
                    <img src="${baseUrl}/frontend/img/others/h8_about_shape01.svg" alt="shape" data-aos="fade-down-right"
                        data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/h8_about_shape02.svg" alt="shape" data-aos="fade-up-left"
                        data-aos-delay="400">
                </div>
                `;
                // Update the components
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