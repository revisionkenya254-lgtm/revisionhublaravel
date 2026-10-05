import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`h8_cta_img-BaN4j2Ut.webp`,import.meta.url).href,n=``+new URL(`join-us-four-D0IYZ0gp.webp`,import.meta.url).href,r=(r,{folderName:i,fileName:a},o)=>{e({editor:r,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`cta__area-four`},traits:[{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`link`,label:`Title`,type:`text`,changeProp:!0},{name:`btn_text`,label:`Title`,type:`text`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0}],title:`Finding Your Right Courses`,description:`Unlock your potential by joining our vibrant learning community`,link:`${o}/register`,btn_text:`Get Started`,banner_img:t,script:function(){$(`.tg-svg`).each(function(){var e=$(this),t=e.find(`.svg-icon`),n=t.attr(`id`),r=t.data(`svg-icon`);if(n){var i=new Vivus(n,{duration:80,file:r});e.on(`mouseenter`,function(){i.reset().play()})}})}},init(){let e=this;e.on(`change:title change:description change:link change:btn_text change:banner_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="cta__inner-wrap">
                                <div class="cta__img-three tg-svg">
                                    <img src="${this.get(`banner_img`)}" alt="${this.get(`title`)}">
                                    <span class="svg-icon" id="cta-svg"
                                        data-svg-icon="${o}/frontend/img/others/rh8_cta_img_shape.svg"></span>
                                </div>
                                <div class="cta__content-three cta__content-four">
                                    <div class="content__left">
                                        <h2 class="title">${this.get(`title`)}</h2>
                                        <p>${this.get(`description`)}}</p>
                                    </div>
                                    <a href="${this.get(`link`)}" class="btn arrow-btn">${this.get(`btn_text`)} <img
                                            src="${o}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btn_text`)}" class="injectable"></a>
                                </div>
                                <img src="${o}/frontend/img/others/h8_cta_shape.svg" alt="shape" class="shape">
                            </div>
                        </div>
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{r as default};