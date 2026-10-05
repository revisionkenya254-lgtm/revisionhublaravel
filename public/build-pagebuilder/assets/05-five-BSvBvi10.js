import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./h4_features_icon03-DEAEfvA3.js";var n=``+new URL(`h4_features_icon01-DPAZBgD_.svg`,import.meta.url).href,r=``+new URL(`h4_features_icon02-avar6lD6.svg`,import.meta.url).href,i=``+new URL(`features_bg-BpvnKdkE.webp`,import.meta.url).href,a=``+new URL(`feature-five-fhQkhjVH.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c},l)=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`features__area-five features__bg section-pt-120 section-pb-90`},traits:[{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{type:`accordion`,label:`Features`,name:`features`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Lorem Ipsum`},{name:`description`,type:`textarea`,label:`Description`,placeholder:`Description`,default:`We are able to offer every yoga training experienced & best yoga trainer.`},{name:`icon`,type:`image-upload`,label:`Icon`,default:n}]}],title:`Connecting Your Mind, Body and Spirit through`,description:`We believe yoga can power transformation on and off the mat We are more than just yogis - we're mom's, home makers, yoga studio owners, epic yoga retreat hosts`,features:[{title:`Support & Motivation`,description:`We are able to offer every yoga training experienced & best yoga trainer.`,icon:n},{title:`Strong Body Life`,description:`We are able to offer every yoga training experienced & best yoga trainer.`,icon:r},{title:`Increased Flexibility`,description:`We are able to offer every yoga training experienced & best yoga trainer.`,icon:t}],background_img:i,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:description change:features change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=(this.get(`features`)||[]).map(e=>`
                    <div class="col-lg-4 col-md-6">
                        <div class="features__item-four">
                            <div class="features__icon-four">
                                <img src="${e.icon}" alt="${e.title}"
                                    class="injectable">
                            </div>
                            <div class="features__content-four">
                                <h3 class="title">${e.title}</h3>
                                <p>${e.description}</p>
                            </div>
                        </div>
                    </div>
                `).join(``),t=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="section__title text-center white-title mb-60">
                                <h2 class="title">${this.get(`title`)}</h2>
                                <p>${this.get(`description`)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="row justify-content-center">
                                ${e}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="features__shape-wrap">
                    <img src="${l}/frontend/img/others/h4_features_shape01.svg" alt="shape" data-aos="fade-right"
                        data-aos-delay="200">
                    <img src="${l}/frontend/img/others/h4_features_shape02.svg" alt="shape" data-aos="fade-left"
                        data-aos-delay="200">
                </div>
                `;this.components(t),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};