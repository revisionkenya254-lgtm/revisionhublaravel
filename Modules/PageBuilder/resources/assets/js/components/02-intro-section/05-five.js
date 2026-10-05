import { registerComponent } from '@utils/registerComponent';
import aboutImg from '@images/others/h7_choose_img01.webp';
import aboutImgTwo from '@images/others/h7_choose_img02.webp';
import aboutImgThree from '@images/others/h7_choose_img03.webp';
import blogManagerImg from '@images/sections/about-five.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "choose__area-four tg-motion-effects section-py-140" },
                traits: [
                    { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'videoLink', label: 'Video Link', type: 'text', changeProp: true },
                    { name: 'about_img', label: 'Image', type: 'image-upload', changeProp: true },
                    { name: 'about_img_two', label: 'Image Two', type: 'image-upload', changeProp: true },
                    { name: 'about_img_three', label: 'Image Three', type: 'image-upload', changeProp: true },
                ],
                subtitle: 'Why Choose Us',
                title: 'Professional Courses taught by industry leaders',
                description: "<p>Discover a vast collection of top-rated courses, covering a wide range of subjects and taught by expert instructors. Whether you're looking to advance your career</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>",
                videoLink: 'https://www.youtube.com/watch?v=pMzGDBP6Bic',
                btnLink: `${baseUrl}/about-us`,
                btnText: 'More Info',
                about_img: aboutImg,
                about_img_two: aboutImgTwo,
                about_img_three: aboutImgThree,
                script: function(){
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
                wrapper.on('change:subtitle change:title change:description change:btnLink change:btnText change:videoLink change:about_img change:about_img_two change:about_img_three',
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
                            <div class="choose__img-four">
                                <div class="left__side">
                                    <img src="${this.get('about_img')}" alt="${this.get('subtitle')}" data-aos="fade-down" data-aos-delay="200">
                                    <img src="${this.get('about_img_two')}" alt="${this.get('subtitle')}" data-aos="fade-up" data-aos-delay="200">
                                </div>
                                <div class="right__side" data-aos="fade-left" data-aos-delay="400">
                                    <img src="${this.get('about_img_three')}" alt="${this.get('subtitle')}">
                                    <a href="${this.get('videoLink')}" class="popup-video" aria-label="Watch introductory video"><i class="fas fa-play"></i></a>
                                </div>
                                <img src="${baseUrl}/frontend/img/others/h7_choose_shape01.svg" alt="shape" class="shape shape-one tg-motion-effects4">
                                <img src="${baseUrl}/frontend/img/others/h7_choose_shape02.svg" alt="shape" class="shape shape-two alltuchtopdown">
                                <img src="${baseUrl}/frontend/img/others/h7_choose_shape03.svg" alt="shape" class="shape shape-three tg-motion-effects7">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="choose__content-four">
                                <div class="section__title mb-20">
                                    <span class="sub-title">${this.get('subtitle')}</span>
                                    <h2 class="title bold">${this.get('title')}</h2>
                                </div>
                                <div>${this.get('description')}</div>
                                <a href="${this.get('btnLink')}" class="btn arrow-btn btn-four">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
                            </div>
                        </div>
                    </div>
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