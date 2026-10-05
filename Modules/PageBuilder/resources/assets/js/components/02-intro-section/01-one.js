import { registerComponent } from '@utils/registerComponent';
import aboutImg from '@images/others/about_img.png';
import aboutShape from '@images/others/about_shape.svg';
import studentGrp from '@images/others/student_grp.png';
import blogManagerImg from '@images/sections/about.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "about-area tg-motion-effects section-py-120" },
                traits: [
                    { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'count', label: 'Count', type: 'text', changeProp: true },
                    { name: 'count_title', label: 'Count Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'ytLink', label: 'YouTube Video Link', type: 'text', changeProp: true },
                    { name: 'about_img', label: 'About Image', type: 'image-upload', changeProp: true },
                    { name: 'about_shape_img', label: 'About Shape', type: 'image-upload', changeProp: true },
                    { name: 'count_img', label: 'Count Image', type: 'image-upload', changeProp: true },
                ],
                subtitle: 'Get More About Us',
                title: 'Thousand Of Top <span class="highlight">Courses</span> Now in One Place',
                count: '36K+',
                count_title: 'Enrolled Students',
                description: "<p>Discover a vast collection of top-rated courses, covering a wide range of subjects and taught by expert instructors. Whether you're looking to advance your career</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>",
                btnLink: `${baseUrl}/about-us`,
                btnText: 'Start Free Trial',
                ytLink: 'https://www.youtube.com/watch?v=b2Az7_lLh3g',
                about_img: aboutImg,
                about_shape_img: aboutShape,
                count_img: studentGrp,
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
                wrapper.on('change:subtitle change:title change:count change:count_title change:description change:btnLink change:btnText change:ytLink change:about_img change:about_shape_img change:count_img',
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
                            <div class="col-lg-6 col-md-9">
                                <div class="about__images">
                                    <img src="${this.get('about_img')}" alt="${this.get('title_one')}" class="main-img">
                                    <img src="${this.get('about_shape_img')}" alt="${this.get('title_one')}" class="shape alltuchtopdown">
                                    <a href="${this.get('ytLink')}" class="popup-video">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="28" viewBox="0 0 22 28" fill="none">
                                            <path d="M0.19043 26.3132V1.69421C0.190288 1.40603 0.245303 1.12259 0.350273 0.870694C0.455242 0.6188 0.606687 0.406797 0.79027 0.254768C0.973854 0.10274 1.1835 0.0157243 1.39936 0.00193865C1.61521 -0.011847 1.83014 0.0480663 2.02378 0.176003L20.4856 12.3292C20.6973 12.4694 20.8754 12.6856 20.9999 12.9535C21.1245 13.2214 21.1904 13.5304 21.1904 13.8456C21.1904 14.1608 21.1245 14.4697 20.9999 14.7376C20.8754 15.0055 20.6973 15.2217 20.4856 15.3619L2.02378 27.824C1.83056 27.9517 1.61615 28.0116 1.40076 27.9981C1.18536 27.9847 0.97607 27.8983 0.792638 27.7472C0.609205 27.596 0.457661 27.385 0.352299 27.1342C0.246938 26.8833 0.191236 26.6008 0.19043 26.3132Z" fill="currentcolor" />
                                        </svg>
                                    </a>
                                    <div class="about__enrolled"  data-aos="fade-right" data-aos-delay="200">
                                        <p class="title"><span>${this.get('count')}</span> ${this.get('count_title')}</p>
                                        <img src="${this.get('count_img')}" alt="${this.get('count_title')}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="about__content">
                                    <div class="section__title">
                                        <span class="sub-title">${this.get('subtitle')}</span>
                                        <h2 class="title">
                                            ${this.get('title')}
                                        </h2>
                                    </div>
                                    ${this.get('description')}
                                    <div class="tg-button-wrap">
                                        <a href="${this.get('btnLink')}" class="btn arrow-btn">${this.get('btnText')} <img data-not-editable="true" src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
                                    </div>  
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