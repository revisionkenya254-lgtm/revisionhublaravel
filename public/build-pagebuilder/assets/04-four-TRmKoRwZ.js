import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./placeholder-DfUPplv7.js";var n=``+new URL(`blog-four-JckxAin4.webp`,import.meta.url).href,r=(e=3)=>{let n=``;for(let r=0;r<e;r++)n+=`
            <div class="col-lg-4 col-md-6">
                <div class="blog__post-item-six shine__animate-item">
                    <div class="blog__post-thumb-six">
                        <a href="#" class="shine__animate-link"><img src="${t}" alt="img"></a>
                        <a href="#" class="post-tag-four">Education</a>
                    </div>
                    <div class="blog__post-content-six">
                        <div class="blog__post-meta">
                            <ul class="list-wrap">
                                <li><i class="flaticon-calendar"></i>04 Jun, 2024</li>
                                <li><i class="flaticon-user-1"></i>by <a href="#">Admin</a></li>
                            </ul>
                        </div>
                        <h2 class="title"><a href="#">Latest Research in Educational Technology</a></h2>
                        <a href="#" class="btn arrow-btn">Read More <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg" data-inject-url="${baseUrl}/frontend/img/icons/right_arrow.svg" class="injectable">
                        <path d="M1 7L15 7M15 7L9 1M15 7L9 13" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M1 7L15 7M15 7L9 1M15 7L9 13" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg></a>
                    </div>
                </div>
            </div>
        `;return n},i=(t,{folderName:i,fileName:a})=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`blog__post-area-eight section-pt-140 section-pb-110`},traits:[{name:`total`,label:`Total Blog`,type:`number`,min:1,changeProp:!0},{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0}],total:3,sub_title:`News & Blogs`,title:`Our Latest News Feed`,description:`when known printer took a galley of type scrambl edmake`,script:function(){e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:total change:sub_title change:title change:description`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get(`sub_title`)}</span>
                                <h2 class="title bold">${this.get(`title`)}</h2>
                                <p>${this.get(`description`)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <!-- DYNAMIC_PART_START:blog-four total=${this.get(`total`)} -->
                            ${r(parseInt(this.get(`total`)))}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="blog__shape-wrap-three">
                    <img src="${baseUrl}/frontend/img/others/h8_blog_shape01.svg" alt="shape" data-aos="fade-down-left"
                        data-aos-delay="400">
                    <img src="${baseUrl}/frontend/img/others/h8_blog_shape02.svg" alt="shape" data-aos="fade-up-right"
                        data-aos-delay="400">
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};