import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`newsletter-two-Btq_Ucrg.webp`,import.meta.url).href,n=(n,{folderName:r,fileName:i},a)=>{e({editor:n,name:i,category:r,media:`<img src="${t}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`newsletter__area-three`},traits:[{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`placeholderText`,label:`Input Placeholder Text`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0}],title:`Start today and get certified in Fundamentals of Business Core`,placeholderText:`Type your email`,btnText:`Subscribe Now`,script:function(){e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:placeholderText change:btnText`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="newsletter__inner-wrap newsletter__inner-wrap-two">
                        <h2 class="title">${this.get(`title`)}</h2>
                        <!-- DYNAMIC_PART_START:newsletter-form-two placeholder_text="${this.get(`placeholderText`)}" button_text="${this.get(`btnText`)}" -->
                            <form action="#" class="newsletter__form-two newsletter">
                                <input type="email" placeholder="${this.get(`placeholderText`)}">
                                <button type="submit" class="btn  arrow-btn">${this.get(`btnText`)}</button>
                            </form>
                        <!-- DYNAMIC_PART_END -->
                        <img src="${a}/frontend/img/others/h7_newsletter_shape01.svg" alt="shape" data-aos="fade-down-right"
                            data-aos-delay="400" class="shape shape-one">
                        <img src="${a}/frontend/img/others/h7_newsletter_shape02.svg" alt="shape" class="shape shape-two rotateme">
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{n as default};