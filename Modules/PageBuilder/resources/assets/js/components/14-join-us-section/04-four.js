import { registerComponent } from '@utils/registerComponent';
import bannerImg from '@images/others/become_student.webp';
import backgroundImg from '@images/others/h4_cta_bg.webp';
import blogManagerImg from '@images/sections/join-us-three.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "cta__area-two cta__bg-two" },
                traits: [
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'link', label: 'Title', type: 'text', changeProp: true },
                    { name: 'btn_text', label: 'Title', type: 'text', changeProp: true },
                    { name: 'banner_img', label: 'Banner Image', type: 'image-upload', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                title: 'My course helps to become Balance in life',
                link: `${baseUrl}/register`,
                btn_text: 'Get Check Available Seat',
                banner_img: bannerImg,
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
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
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:link change:btn_text change:banner_img change:background_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="cta__img">
                                <img src="${this.get('banner_img')}" alt="${this.get('title')}">
                                <div class="shape">
                                    <img src="${baseUrl}/frontend/img/others/h4_cta_shape.svg" alt="shape" class="rotateme">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="cta__content-two">
                                <h2 class="title">${this.get('title')}</h2>
                                <div class="cta__btn">
                                    <a href="${this.get('link')}" class="btn">${this.get('btn_text')}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cta__shape">
                    <img src="${baseUrl}/frontend/img/others/h4_cta_shape02.svg" alt="shape" data-aos="fade-left" data-aos-delay="400">
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
