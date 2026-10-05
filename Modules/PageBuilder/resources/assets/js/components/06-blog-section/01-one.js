import { registerComponent } from '@utils/registerComponent';
import placeholderImage from '@images/others/placeholder.jpg';
import blogManagerImg from '@images/sections/blog.webp';

const generateHTML = (total = 4) => {
    let html = '';
    for (let i = 0; i < total; i++) {
        html += `
            <div class="col-xl-3 col-md-6">
                <div class="blog__post-item shine__animate-item">
                    <div class="blog__post-thumb">
                        <a href="/blogs" class="shine__animate-link"><img src="${placeholderImage}" alt="img"/></a>
                        <a href="/blogs" class="post-tag">Marketing</a>
                    </div>
                    <div class="blog__post-content">
                        <div class="blog__post-meta">
                            <ul class="list-wrap">
                                <li><i class="flaticon-calendar"></i>20 July, 2024</li>
                                <li>
                                <i class="flaticon-user-1"></i>by
                                <a href="#">Admin</a>
                                </li>
                            </ul>
                        </div>
                        <h4 class="title"><a href="/blogs">Blog Title</a></h4>
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
                attributes: { class: "blog__post-area" },
                traits: [
                    { name: 'total', label: 'Total Blog', type: 'number', min: 1, changeProp: true },
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                ],

                // Default values
                total: 4,
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
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-6">
                                <div class="section__title text-center mb-40">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title">${this.get('title')}</h2>
                                    <p>${this.get('description')}</p>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:blog total=${this.get('total')} -->
                        <div class="row gutter-20">
                            ${generateHTML(parseInt(this.get('total')))}
                        </div>
                        <!-- DYNAMIC_PART_END -->
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