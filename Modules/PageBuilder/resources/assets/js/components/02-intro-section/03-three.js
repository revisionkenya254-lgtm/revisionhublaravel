import { registerComponent } from '@utils/registerComponent';
import aboutImg from '@images/others/about_img.png';
import aboutShape from '@images/others/about_shape.svg';
import featureIconOne from '@images/icons/profit.svg';
import featureIconTwo from '@images/icons/mortarboard.svg';
import blogManagerImg from '@images/sections/about-three.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "work__area section-pt-120 section-pb-120" },
                traits: [
                    { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'ytLink', label: 'YouTube Video Link', type: 'text', changeProp: true },
                    { name: 'about_img', label: 'About Image', type: 'image-upload', changeProp: true },
                    { name: 'about_shape_img', label: 'About Shape', type: 'image-upload', changeProp: true },
                    { name: 'feature_one_title', label: 'Feature One Title', type: 'text', changeProp: true },
                    { name: 'feature_one_description', label: 'Feature One Description', type: 'textarea', changeProp: true },
                    { name: 'feature_one_img', label: 'Feature One Icon', type: 'image-upload', changeProp: true },
                    { name: 'feature_two_title', label: 'Feature Two Title', type: 'text', changeProp: true },
                    { name: 'feature_two_description', label: 'Feature Two Description', type: 'textarea', changeProp: true },
                    { name: 'feature_two_img', label: 'Feature Two Icon', type: 'image-upload', changeProp: true },
                ],
                subtitle: 'Free Workshop',
                title: 'Join Our Free Workshops',
                description: "Edhen an unknown printer took a galley of type and scrambled it to make a type specimen bookas survived not only five centuries.Edhen an unknown printer took a galley of type and scrambled.",
                btnLink: `${baseUrl}/about-us`,
                btnText: 'Quick Join Now',
                ytLink: 'https://www.youtube.com/watch?v=b2Az7_lLh3g',
                about_img: aboutImg,
                about_shape_img: aboutShape,
                feature_one_title: 'Smooth Virtual Live Classes',
                feature_one_description: 'Edhen an unknown printer Rtook galley of type scrambled.',
                feature_one_img: featureIconOne,
                feature_two_title: '99% Graduation Complete',
                feature_two_description: 'Edhen an unknown printer Rtook galley of type scrambled.',
                feature_two_img: featureIconTwo,
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:subtitle change:title change:description change:feature_one_title change:feature_one_description change:feature_one_img change:feature_two_title change:feature_two_description change:feature_two_img change:btnLink change:btnText change:ytLink change:about_img change:about_shape_img',
                    () => wrapper.updateContent()
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                    <div class="container">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-lg-6 col-md-9">
                                <div class="about__images work__images">
                                    <img src="${this.get('about_img')}" alt="${this.get('title')}" class="main-img">
                                    <img src="${this.get('about_shape_img')}" alt="${this.get('title')}" class="shape alltuchtopdown">
                                    <a href="${this.get('ytLink')}" class="popup-video">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="28" viewBox="0 0 22 28" fill="none">
                                            <path d="M0.19043 26.3132V1.69421C0.190288 1.40603 0.245303 1.12259 0.350273 0.870694C0.455242 0.6188 0.606687 0.406797 0.79027 0.254768C0.973854 0.10274 1.1835 0.0157243 1.39936 0.00193865C1.61521 -0.011847 1.83014 0.0480663 2.02378 0.176003L20.4856 12.3292C20.6973 12.4694 20.8754 12.6856 20.9999 12.9535C21.1245 13.2214 21.1904 13.5304 21.1904 13.8456C21.1904 14.1608 21.1245 14.4697 20.9999 14.7376C20.8754 15.0055 20.6973 15.2217 20.4856 15.3619L2.02378 27.824C1.83056 27.9517 1.61615 28.0116 1.40076 27.9981C1.18536 27.9847 0.97607 27.8983 0.792638 27.7472C0.609205 27.596 0.457661 27.385 0.352299 27.1342C0.246938 26.8833 0.191236 26.6008 0.19043 26.3132Z" fill="currentcolor" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="work__content">
                                    <div class="section__title mb-20">
                                        <span class="sub-title">${this.get('subtitle')}</span>
                                        <h2 class="title">${this.get('title')}</h2>
                                    </div>
                                    <p>${this.get('description')}</p>
                                    <div class="work__list-wrap">
                                        <div class="work__list-box">
                                            <div class="work__list-box-top">
                                                <div class="icon">
                                                    <img src="${this.get('feature_one_img')}" alt="${this.get('feature_one_title')}" class="injectable">
                                                </div>
                                                <h4 class="title">${this.get('feature_one_title')}</h4>
                                            </div>
                                            <p>${this.get('feature_one_description')}</p>
                                        </div>
                                        <div class="work__list-box">
                                            <div class="work__list-box-top">
                                                <div class="icon">
                                                    <img src="${this.get('feature_two_img')}" alt="${this.get('feature_two_title')}" class="injectable">
                                                </div>
                                                <h4 class="title">${this.get('feature_two_title')}</h4>
                                            </div>
                                            <p>${this.get('feature_two_description')}</p>
                                        </div>
                                    </div>
                                    <div class="tg-button-wrap">
                                        <a href="${this.get('btnLink')}" class="btn arrow-btn">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
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