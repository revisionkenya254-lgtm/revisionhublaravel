import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./become_student-BVwAnC5m.js";var n=``+new URL(`h7_cta_bg-CaBPU4tf.webp`,import.meta.url).href,r=``+new URL(`calll-to-action-three-Byk1jYcF.webp`,import.meta.url).href,i=(i,{folderName:a,fileName:o},s)=>{e({editor:i,name:o,category:a,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`cta__area-three`},traits:[{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`link`,label:`Title`,type:`text`,changeProp:!0},{name:`btn_text`,label:`Title`,type:`text`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],title:`Finding Your Right Courses`,description:`Unlock your potential by joining our vibrant learning community`,link:`${s}/register`,btn_text:`Get Started`,banner_img:t,background_img:n,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)})}},init(){let e=this;e.on(`change:title change:description change:link change:btn_text change:banner_img change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="cta__bg-three" data-background="${this.get(`background_img`)}">
                                <div class="cta__img-two">
                                    <img src="${this.get(`banner_img`)}" alt="${this.get(`title`)}">
                                </div>
                                <div class="cta__content-three">
                                    <div class="content__left">
                                        <h2 class="title">${this.get(`title`)}</h2>
                                        <p>${this.get(`description`)}}</p>
                                    </div>
                                    <a href="${this.get(`link`)}" class="btn arrow-btn">${this.get(`btn_text`)} <img src="${s}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btn_text`)}" class="injectable"></a>
                                </div>
                                <div class="cta__shape-two">
                                    <img src="${s}/frontend/img/others/h7_cta_shape.svg" alt="shape">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};