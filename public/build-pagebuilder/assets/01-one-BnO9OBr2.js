import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`cta_bg-B8g8kKH-.webp`,import.meta.url).href,n=``+new URL(`call-two-action-two-BA1xk60b.webp`,import.meta.url).href,r=(r,{folderName:i,fileName:a},o)=>{e({editor:r,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`cta__area home_3_cta`},traits:[{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`link`,label:`Link`,type:`text`,changeProp:!0},{name:`btn_text`,label:`Title`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],title:`Together We Go Far`,description:`Through research and discovery, we are changing the world.`,link:`${o}/register`,btn_text:`Join With Us`,background_img:t,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)})}},init(){let e=this;e.on(`change:title change:description change:link change:btn_text change:background_img`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=`
                <div class="cta__bg" data-background="${this.get(`background_img`)}"></div>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="cta__content">
                                <h2 class="title">${this.get(`title`)}</h2>
                                <p>${this.get(`description`)}</p>
                                <a href="${this.get(`link`)}" class="btn arrow-btn">${this.get(`btn_text`)} <img
                                        src="${o}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btn_text`)}"
                                        class="injectable"></a>
                            </div>
                        </div>
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{r as default};