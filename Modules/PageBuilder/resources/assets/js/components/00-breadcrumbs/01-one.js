import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/breadcrumb.webp';
import mediaImage from '@images/sections/breadcrumb.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "div",
                attributes: { class: "breadcrumb__area breadcrumb__bg" },
                traits: [
                    { name: 'page_title', label: 'Page Title', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                    {
                        name: 'links', label: "Links", type: 'accordion', changeProp: true,
                        inputsConfig: [
                            { name: 'title', label: 'Title', type: 'text', changeProp: true, default: "Page Name" },
                            { name: 'url', label: 'URL', type: 'text', changeProp: true, default: "#" },
                        ]
                    },
                ],
                page_title: "Blogs",
                background_img: backgroundImg,
                links: [
                    { title: 'Home', url: '/' },
                    { title: 'Blogs', url: "#" },
                ],
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
                wrapper.on('change:page_title change:background_img change:links', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const links = this.get('links') || [];
                const generateHTML = links.map((link, index) => {
                    const isLast = index === links.length - 1;
                    const url = link.url && !isLast ? link.url : 'javascript:;';
                    let html = `<span property="itemListElement" typeof="ListItem"><a href="${url}">${link.title}</a></span>`;

                    if (!isLast) {
                        html += `<span class="breadcrumb-separator"><i class="fas fa-angle-right"></i></span>`;
                    }
                    return html;
                }).join('');

                const html = `
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="breadcrumb__content">
                                <h3 class="title">${this.get('page_title')}</h3>
                                <nav class="breadcrumb">
                                    ${generateHTML}
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="breadcrumb__shape-wrap">
                    <img src="${baseUrl}/frontend/img/others/breadcrumb_shape01.svg" alt="img" class="alltuchtopdown">
                    <img src="${baseUrl}/frontend/img/others/breadcrumb_shape02.svg" alt="img" data-aos="fade-right"
                        data-aos-delay="300">
                    <img src="${baseUrl}/frontend/img/others/breadcrumb_shape03.svg" alt="img" data-aos="fade-up"
                        data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/breadcrumb_shape04.svg" alt="img" data-aos="fade-down-left"
                        data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/breadcrumb_shape05.svg" alt="img" data-aos="fade-left"
                        data-aos-delay="400">
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