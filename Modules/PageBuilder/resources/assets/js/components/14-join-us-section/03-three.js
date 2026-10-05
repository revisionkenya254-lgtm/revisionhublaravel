import { registerComponent } from '@utils/registerComponent';
import bannerImg from '@images/others/h8_cta_img.webp';
import blogManagerImg from '@images/sections/join-us-four.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "cta__area-four" },
                traits: [
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'link', label: 'Title', type: 'text', changeProp: true },
                    { name: 'btn_text', label: 'Title', type: 'text', changeProp: true },
                    { name: 'banner_img', label: 'Banner Image', type: 'image-upload', changeProp: true },
                ],
                title: 'Finding Your Right Courses',
                description: 'Unlock your potential by joining our vibrant learning community',
                link: `${baseUrl}/register`,
                btn_text: 'Get Started',
                banner_img: bannerImg,
                script: function () {
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
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:description change:link change:btn_text change:banner_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="cta__inner-wrap">
                                <div class="cta__img-three tg-svg">
                                    <img src="${this.get('banner_img')}" alt="${this.get('title')}">
                                    <span class="svg-icon" id="cta-svg"
                                        data-svg-icon="${baseUrl}/frontend/img/others/rh8_cta_img_shape.svg"></span>
                                </div>
                                <div class="cta__content-three cta__content-four">
                                    <div class="content__left">
                                        <h2 class="title">${this.get('title')}</h2>
                                        <p>${this.get('description')}}</p>
                                    </div>
                                    <a href="${this.get('link')}" class="btn arrow-btn">${this.get('btn_text')} <img
                                            src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btn_text')}" class="injectable"></a>
                                </div>
                                <img src="${baseUrl}/frontend/img/others/h8_cta_shape.svg" alt="shape" class="shape">
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
