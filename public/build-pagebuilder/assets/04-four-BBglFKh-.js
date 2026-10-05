import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./become_student-BVwAnC5m.js";var n=``+new URL(`h4_cta_bg-Dom5y2D3.webp`,import.meta.url).href,r=``+new URL(`join-us-three-DA3vfSzm.webp`,import.meta.url).href,i=(i,{folderName:a,fileName:o},s)=>{e({editor:i,name:o,category:a,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`cta__area-two cta__bg-two`},traits:[{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`link`,label:`Title`,type:`text`,changeProp:!0},{name:`btn_text`,label:`Title`,type:`text`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],title:`My course helps to become Balance in life`,link:`${s}/register`,btn_text:`Get Check Available Seat`,banner_img:t,background_img:n,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:link change:btn_text change:banner_img change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="cta__img">
                                <img src="${this.get(`banner_img`)}" alt="${this.get(`title`)}">
                                <div class="shape">
                                    <img src="${s}/frontend/img/others/h4_cta_shape.svg" alt="shape" class="rotateme">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="cta__content-two">
                                <h2 class="title">${this.get(`title`)}</h2>
                                <div class="cta__btn">
                                    <a href="${this.get(`link`)}" class="btn">${this.get(`btn_text`)}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cta__shape">
                    <img src="${s}/frontend/img/others/h4_cta_shape02.svg" alt="shape" data-aos="fade-left" data-aos-delay="400">
                </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};