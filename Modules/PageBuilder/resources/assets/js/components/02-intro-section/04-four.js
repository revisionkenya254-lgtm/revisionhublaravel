import { registerComponent } from '@utils/registerComponent';
import aboutImg from '@images/others/theme_university_about_img.webp';
import studentGrp from '@images/others/student_grp.png';
import blogManagerImg from '@images/sections/about-four.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "about-area-four section-pb-120" },
                traits: [
                    { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'videoLink', label: 'Video Link', type: 'text', changeProp: true },
                    { name: 'videoBtnText', label: 'Video Button Text', type: 'text', changeProp: true },
                    { name: 'count', label: 'Count', type: 'text', changeProp: true },
                    { name: 'count_title', label: 'Count Title', type: 'text', changeProp: true },
                    { name: 'count_img', label: 'Count Image', type: 'image-upload', changeProp: true },
                    { name: 'count_two', label: 'Count Two', type: 'text', changeProp: true },
                    { name: 'count_two_title', label: 'Count Two Title', type: 'text', changeProp: true },
                    { name: 'about_img', label: 'About Image', type: 'image-upload', changeProp: true },
                ],
                subtitle: 'About Campus history',
                title: 'We Are One of The Largest, Most <br> Diverse Universities in The USA',
                description: "<p>Preparing students to make meaningful contributions to society as leaders in a complex wPreparing students to make meaningful contribution.Preparing students to make contributions to society as leaders in a complex.</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>",
                videoLink: 'https://www.youtube.com/watch?v=pMzGDBP6Bic',
                videoBtnText: 'Watch Our <br> Video',
                about_img: aboutImg,
                count_img: studentGrp,
                btnLink: `${baseUrl}/about-us`,
                btnText: 'Know More',
                count: '36K+',
                count_title: 'Enrolled Students',
                count_two: '15',
                count_two_title: 'year <br> of Institutes',
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
                wrapper.on('change:subtitle change:title change:count change:count_title change:count_two change:count_two_title change:description change:btnLink change:btnText change:videoLink change:videoBtnText change:about_img change:count_img',
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
                            <div class="col-lg-5 col-md-9">
                                <div class="about__images-four">
                                    <img src="${this.get('about_img')}"
                                        alt="${this.get('subtitle')}">
                                        <div class="about__enrolled" data-aos="fade-up" data-aos-delay="400">
                                            <p class="title"><span>${this.get('count')}</span>
                                                ${this.get('count_title')}</p>
                                            <img src="${this.get('count_img')}" alt="${this.get('count_title')}">
                                        </div>
                                        <div class="about__video" data-aos="fade-left" data-aos-delay="400">
                                            <a href="${this.get('videoLink')}" class="play-btn popup-video" aria-label="Watch introductory video"><i
                                                    class="fas fa-play"></i> ${this.get('videoBtnText')}</a>
                                        </div>
                                    <div class="about__year-wrap">
                                        <h2 class="count">${this.get('count_two')}</h2>
                                        <h5 class="title">${this.get('count_two_title')}</h5>
                                    </div>
                                    <div class="shape">
                                        <img src="${baseUrl}/frontend/img/others/h3_about_shape01.svg" alt="shape"
                                            data-aos="fade-right" data-aos-delay="200" class="alltuchtopdown">
                                        <img src="${baseUrl}/frontend/img/others/h3_about_shape02.svg" alt="shape"
                                            data-aos="fade-right" data-aos-delay="200">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="about__content-four">
                                    <div class="section__title mb-15">
                                        <span class="sub-title">${this.get('subtitle')}</span>
                                        <h2 class="title">${this.get('title')}</h2>
                                    </div>
                                    ${this.get('description')}
                                    <a href="${this.get('btnLink')}"
                                        class="btn arrow-btn">${this.get('btnText')} <img
                                            src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('subtitle')}"
                                            class="injectable"></a>
                                    <img src="${baseUrl}/frontend/img/others/h3_about_shape03.svg" alt="shape" class="shape alltuchtopdown">
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