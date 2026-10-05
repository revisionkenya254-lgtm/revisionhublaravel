import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`newsletter-7qn64jp6.webp`,import.meta.url).href,n=``+new URL(`newsletter_shape01-CbJGmRpD.png`,import.meta.url).href,r=``+new URL(`newsletter_shape02-BFbetvgw.png`,import.meta.url).href,i=``+new URL(`newsletter_shape03-Dcqpxrrb.png`,import.meta.url).href,a=``+new URL(`newsletter-CoD9oZSj.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c})=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`newsletter__area`},traits:[{name:`title`,label:`Title Part One`,type:`textarea`,changeProp:!0},{name:`placeholderText`,label:`Input Placeholder Text`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`image`,label:`Image`,type:`image-upload`,changeProp:!0},{name:`shape_one`,label:`Shape One`,type:`image-upload`,changeProp:!0},{name:`shape_two`,label:`Shape Two`,type:`image-upload`,changeProp:!0},{name:`shape_three`,label:`Shape Three`,type:`image-upload`,changeProp:!0}],title:`Want to stay informed about <br> new courses and study`,placeholderText:`Type your email`,btnText:`Subscribe Now`,image:t,shape_one:n,shape_two:r,shape_three:i,script:function(){e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:placeholderText change:btnText change:image change:shape_one change:shape_two change:shape_three`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-4">
                                <div class="newsletter__img-wrap">
                                    <img src="${this.get(`image`)}" alt="${this.get(`title`)}">
                                    <img src="${this.get(`shape_one`)}" alt="shape" data-aos="fade-up"
                                        data-aos-delay="400">
                                    <img src="${this.get(`shape_two`)}" alt="shape" class="alltuchtopdown">
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="newsletter__content">
                                    <h2 class="title"><b>${this.get(`title`)}</b></h2>
                                    <div class="newsletter__form">
                                        <!-- DYNAMIC_PART_START:newsletter-form placeholder_text="${this.get(`placeholderText`)}" button_text="${this.get(`btnText`)}" -->
                                        <form action="#">
                                            <input type="email" placeholder="${this.get(`placeholderText`)}">
                                            <button type="submit" class="btn">${this.get(`btnText`)}</button>
                                        </form>
                                        <!-- DYNAMIC_PART_END -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="newsletter__shape">
                        <img src="${this.get(`shape_three`)}" alt="shape" data-aos="fade-left" data-aos-delay="400">
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};