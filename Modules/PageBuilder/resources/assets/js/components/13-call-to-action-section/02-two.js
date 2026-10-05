import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/h4_video_bg.webp';
import blogManagerImg from '@images/sections/call-to-action-two.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "video__area home_3_cta" },
                traits: [
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'video_link', label: 'Video Link', type: 'text', changeProp: true },
                    { name: 'link', label: 'Link', type: 'text', changeProp: true },
                    { name: 'btn_text', label: 'Title', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                title: 'Finding Your Right Courses',
                description: 'Unlock your potential by joining our vibrant learning community',
                link: `${baseUrl}/register`,
                video_link: 'https://www.youtube.com/watch?v=b2Az7_lLh3g',
                btn_text: 'Join class Now',
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });

                    SVGInject(document.querySelectorAll("img.injectable"));
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:description change:video_link change:link change:btn_text change:background_img', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="video__bg" data-background="${this.get('background_img')}"></div>
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-xl-7 col-lg-6 order-0 order-lg-2">
                            <div class="video__play-btn">
                                <a href="${this.get('video_link')}" class="popup-video" aria-label="Join Us"><i
                                        class="fas fa-play"></i></a>
                            </div>
                        </div>
                        <div class="col-xl-5 col-lg-6">
                            <div class="video__content">
                                <h2 class="title">${this.get('title')}</h2>
                                        <p>${this.get('description')}</p>
                                <a href="${this.get('link')}" class="btn btn-three arrow-btn">${this.get('btn_text')} <img
                                        src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="" class="injectable"></a>
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
