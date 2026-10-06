import{t as e}from"./registerComponent-vPl6l5lM.js";import{i as t,n,r}from"./features_icon04-BA3Grkuf.js";var i=``+new URL(`feature-two-B0HRkxHu.webp`,import.meta.url).href,a=(a,{folderName:o,fileName:s},c)=>{e({editor:a,name:s,category:o,media:`<img src="${i}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`features__area-two section-pt-120 section-pb-90`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{type:`accordion`,label:`Features`,name:`features`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Lorem Ipsum`},{name:`description`,type:`textarea`,label:`Description`,placeholder:`Description`,default:`when an unknown printer took a galley offer type and scrambled makes.`},{name:`icon`,type:`image-upload`,label:`Icon`,default:t}]}],sub_title:`Our Top Features`,title:`Achieve Your Goal With RevisionHubKenyaw`,description:`when an unknown printer took a galley of type and scrambled make <br> specimen book has survived not only five centuries`,features:[{title:`Learn with Experts`,description:`when an unknown printer took a galley offer type and scrambled makes.`,icon:t},{title:`Effective Courses`,description:`when an unknown printer took a galley offer type and scrambled makes.`,icon:r},{title:`Earn Certificate`,description:`when an unknown printer took a galley offer type and scrambled makes.`,icon:n}]},init(){let e=this;e.on(`change:sub_title change:title change:description change:features`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=(this.get(`features`)||[]).map(e=>`
                    <div class="col-lg-4 col-md-6">
                            <div class="features__item-two">
                                <div class="features__content-two">
                                    <div class="content-top">
                                        <div class="features__icon-two">
                                            <img src="${e.icon}" alt="${e.title}" class="injectable">
                                        </div>
                                        <h2 class="title">${e.title}</h2>
                                    </div>
                                    <p>${e.description}</p>
                                </div>
                                <div class="features__item-shape">
                                    <img src="${c}/frontend/img/others/features_item_shape.svg" alt="img" class="injectable">
                                </div>
                            </div>
                        </div>
                `).join(``),t=`
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-xl-6 col-lg-8">
                                <div class="section__title text-center mb-40">
                                    <span class="sub-title">${this.get(`sub_title`)}</span>
                                    <h2 class="title">${this.get(`title`)}</h2>
                                    <p>${this.get(`description`)}</p>
                                </div>
                            </div>
                        </div>
                        <div class="features__item-wrap">
                            <div class="row justify-content-center">
                        ${e}
                            </div>
                        </div>
                    </div>
                `;this.components(t),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{a as default};