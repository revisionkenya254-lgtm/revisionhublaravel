import { registerComponent } from '@utils/registerComponent';
import aboutImg from '@images/others/h6_choose_img.webp';
import blogManagerImg from '@images/sections/about-eight.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "choose__area-three section-pb-140" },
                traits: [
                    { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'test', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'videoLink', label: 'Video Link', type: 'text', changeProp: true },
                    { name: 'about_img', label: 'Image', type: 'image-upload', changeProp: true },
                ],
                subtitle: 'Why Choose Us',
                title: 'Professional Courses taught by industry leaders',
                description: "<p>Discover a vast collection of top-rated courses, covering a wide range of subjects and taught by expert instructors. Whether you're looking to advance your career</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>",
                videoLink: 'https://www.youtube.com/watch?v=pMzGDBP6Bic',
                btnLink: `${baseUrl}/about-us`,
                btnText: 'More Info',
                about_img: aboutImg,
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:subtitle change:title change:description change:btnLink change:btnText change:videoLink change:about_img',
                    () => wrapper.updateContent()
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6 col-md-10">
                            <div class="choose__img-three">
                                <img src="${this.get('about_img')}" alt="${this.get('subtitle')}">
                                    <a href="${this.get('videoLink')}" class="play-btn popup-video" aria-label="Watch introductory video"><i
                                            class="fas fa-play"></i></a>

                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="choose__content-three">
                                <div class="section__title mb-15">
                                    <span class="sub-title">${this.get('subtitle')}</span>
                                    <h2 class="title bold">${this.get('title')}</h2>
                                </div>
                                <div class=" wsus_content-box">
                                    ${this.get('description')}
                                </div>
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