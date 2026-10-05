import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/placeholder.jpg';
import blogManagerImg from '@images/sections/blog-five.webp';

const generateHTML = (total = 3) => {
    let html = '';
    for (let i = 0; i < total; i++) {
        html += `
            <div class="col-lg-4 col-md-6">
                <div class="blog__post-item-four shine__animate-item">
                    <div class="blog__post-thumb-four">
                        <a href="#" class="shine__animate-link"><img src="${placeholderImage}" alt="img"></a>
                    </div>
                    <div class="blog__post-content-four">
                        <a href="#" class="post-tag-three">Education</a>
                        <h2 class="title"><a href="#">Latest Research in Educational Technology</a></h2>
                        <div class="blog__post-meta">
                            <ul class="list-wrap">
                                <li><i class="flaticon-user-1"></i>by <a href="javascript:;">Admin</a></li>
                                <li><i class="flaticon-calendar"></i>04 Jun, 2024</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    return html;
};
export default (editor,{folderName, fileName} ) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "blog__post-area-six section-pt-140 section-pb-110" },
                traits: [
                    { name: 'total', label: 'Total Blog', type: 'number', min: 1, changeProp: true },
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                ],

                // Default values
                total: 3,
                sub_title: 'News & Blogs',
                title: 'Our Latest News Feed',
                description: 'when known printer took a galley of type scrambl edmake',
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:total change:sub_title change:title change:description', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container home_language">
                    <div class="row justify-content-center">
                        <div class="col-xl-6">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title">${this.get('title')}</h2>
                                <p>${this.get('description')}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <!-- DYNAMIC_PART_START:blog-five total=${this.get('total')} -->
                            ${generateHTML(parseInt(this.get('total')))}
                        <!-- DYNAMIC_PART_END -->
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