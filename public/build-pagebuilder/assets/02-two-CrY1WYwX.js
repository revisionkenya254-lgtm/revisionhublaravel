import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./placeholder-DfUPplv7.js";var n=``+new URL(`blog-two-CE8JrdYS.webp`,import.meta.url).href,r=(e=3)=>{let n=``;for(let r=0;r<e;r++)n+=`
        <div class="col-lg-4 col-md-6">
                <div class="blog__post-item-five shine__animate-item">
                    <div class="blog__post-thumb-five">
                        <a href="#" class="shine__animate-link"><img
                                src="${t}" alt="Blog Title"></a>
                    </div>
                    <div class="blog__post-content-five">
                        <a href="#"
                            class="post-tag-four">Category</a>
                        <h2 class="title"><a
                                href="#">Blog Title</a>
                        </h2>
                        <div class="blog__post-meta">
                            <ul class="list-wrap">
                                <li><i class="flaticon-calendar"></i>04 Jun, 2024</li>
                                <li><i class="flaticon-user-1"></i>by <a
                                        href="#">John Doe</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;return n},i=(t,{folderName:i,fileName:a})=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`blog__post-area-seven section-pt-140 section-pb-110`},traits:[{name:`total`,label:`Total Blog`,type:`number`,min:1,changeProp:!0},{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0}],total:3,sub_title:`News & Blogs`,title:`Our Latest News Feed`,description:`when known printer took a galley of type scrambl edmake`},init(){let e=this;e.on(`change:total change:sub_title change:title change:description`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=`
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
                        <!-- DYNAMIC_PART_START:blog-two total=${this.get(`total`)} -->
                            ${r(parseInt(this.get(`total`)))}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};