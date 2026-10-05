import { registerComponent } from "@utils/registerComponent";
import placeholderImage from "@images/others/placeholder.jpg";
import blogManagerImg from "@images/sections/blog-three.webp";

const generateHTML = (total = 1) => {
    let html = "";
    for (let i = 0; i < total; i++) {
        html += `
            <div class="col-lg-6">
                <div class="blog__post-item-two yoga_featured_blog">
                    <div class="blog__post-thumb-two">
                        <a href="https://skillgro.websolutionus.com/blog/top-classroom-resources-for-teachers">
                            <img src="${placeholderImage}" alt="img">
                        </a>
                    </div>
                    <div class="blog__post-content-two">
                        <a href="https://skillgro.websolutionus.com/blog?category=evaluation" class="post-tag-two">
                            Evaluation
                        </a>
                        <div class="blog__post-meta blog__post-meta-two">
                            <ul class="list-wrap">
                                <li><i class="flaticon-calendar"></i>04 Jun, 2024</li>
                                <li><i class="flaticon-user-1"></i>by
                                    <a href="https://skillgro.websolutionus.com/blog/top-classroom-resources-for-teachers">
                                        Admin
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <h2 class="title">
                            <a href="https://skillgro.websolutionus.com/blog/top-classroom-resources-for-teachers">
                                Top Classroom Resources for Teachers
                            </a>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="blog__post-item-three shine__animate-item">
                    <div class="blog__post-thumb-three">
                        <a href="£" class="shine__animate-link"><img src="${placeholderImage}" alt="img"></a>
                    </div>
                    <div class="blog__post-content-three">
                        <a href="https://skillgro.websolutionus.com/blog?category=education" class="post-tag-two">Education</a>
                        <div class="blog__post-meta">
                            <ul class="list-wrap">
                                <li><i class="flaticon-calendar"></i>04 Jun, 2024</li>
                                <li><i class="flaticon-user-1"></i>by <a href="£">Admin</a>
                                </li>
                            </ul>
                        </div>
                        <h2 class="title"><a href="£">Education</a>
                        </h2>
                    </div>
                </div>
                <div class="blog__post-item-three shine__animate-item">
                    <div class="blog__post-thumb-three">
                        <a href="£" class="shine__animate-link"><img src="${placeholderImage}" alt="img"></a>
                    </div>
                    <div class="blog__post-content-three">
                        <a href="https://skillgro.websolutionus.com/blog?category=strategies" class="post-tag-two">Strategies</a>
                        <div class="blog__post-meta">
                            <ul class="list-wrap">
                                <li><i class="flaticon-calendar"></i>04 Jun, 2024</li>
                                <li><i class="flaticon-user-1"></i>by <a href="£">Admin</a>
                                </li>
                            </ul>
                        </div>
                        <h2 class="title"><a href="£">Strategies</a>
                        </h2>
                    </div>
                </div>
            </div>
        `;
    }
    return html;
};
export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: {
                    class: "blog__post-area-four section-pt-140 section-pb-110",
                },
                traits: [
                    {
                        name: "sub_title",
                        label: "Sub Title",
                        type: "text",
                        changeProp: true,
                    },
                    {
                        name: "title",
                        label: "Title",
                        type: "text",
                        changeProp: true,
                    },
                    {
                        name: "description",
                        label: "Description",
                        type: "textarea",
                        changeProp: true,
                    },
                ],

                // Default values
                sub_title: "News & Blogs",
                title: "Our Latest News Feed",
                description:
                    "when known printer took a galley of type scrambl edmake",
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on(
                    "change:sub_title change:title change:description",
                    () => wrapper.updateContent()
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6">
                            <div class="section__title text-center mb-40">
                                <span class="sub-title">${this.get(
                                    "sub_title"
                                )}</span>
                                <h2 class="title">${this.get("title")}</h2>
                                <p>${this.get("description")}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- DYNAMIC_PART_START:blog-three total=3 -->
                            ${generateHTML()}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="blog__shape-wrap">
                    <img src="${baseUrl}/frontend/img/blog/h4_blog_shape.svg" alt="shape" class="rotateme">
                    <img src="${baseUrl}/frontend/img/blog/h4_blog_shape.svg" alt="shape" class="rotateme">
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
