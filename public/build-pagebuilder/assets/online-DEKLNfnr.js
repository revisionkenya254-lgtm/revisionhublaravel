import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./student_grp-C3uvedIu.js";import{t as n}from"./h2_banner_icon-B5syVA65.js";var r=``+new URL(`theme_online_hero_bg-qNfHzlRJ.webp`,import.meta.url).href,i=``+new URL(`theme_online_banner_img-CNQPe1PU.webp`,import.meta.url).href,a=``+new URL(`theme_online_banner_bg-BkIVoFWf.svg`,import.meta.url).href,o=``+new URL(`hero-two-CZHZcal0.webp`,import.meta.url).href,s=(s,{folderName:c,fileName:l},u)=>{e({editor:s,name:l,category:c,media:`<img src="${o}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`banner-area-two banner-bg-two tg-motion-effects`},traits:[{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`videoLink`,label:`Video Link`,type:`text`,changeProp:!0},{name:`videoBtnText`,label:`Video Button Text`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0},{name:`banner_shape_img`,label:`Banner Background Image`,type:`image-upload`,changeProp:!0},{name:`count`,label:`Count`,type:`text`,changeProp:!0},{name:`count_title`,label:`Count Title`,type:`text`,changeProp:!0},{name:`count_img`,label:`Count Image`,type:`image-upload`,changeProp:!0},{name:`count_two`,label:`Count Two`,type:`text`,changeProp:!0},{name:`count_two_title`,label:`Count Two Title`,type:`text`,changeProp:!0},{name:`count_two_img`,label:`Count Two Image`,type:`image-upload`,changeProp:!0}],title:`Learning is <span class="highlight">What You</span> Make of it. Make it Yours at SkillGro.`,btnLink:`${u}/contact`,btnText:`Start Free Trial`,videoLink:`https://www.youtube.com/watch?v=pMzGDBP6Bic`,videoBtnText:`Watch Our <br> Class Demo`,count:`36K+`,count_title:`Enrolled Students`,count_img:t,banner_img:i,banner_shape_img:a,background_img:r,count_two:`30K`,count_two_title:`Total Instructors`,count_two_img:n,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:btnLink change:btnText change:videoLink change:videoBtnText change:count change:count_title change:count_two change:count_two_title change:banner_img change:banner_shape_img change:background_img change:count_img change:count_two_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="container banner-area-two-container">
                        <div class="row justify-content-center">
                            <div class="col-xl-5 col-lg-6">
                                <div class="banner__content-two">
                                    <h3 class="title" data-aos="fade-right" data-aos-delay="400">
                                        ${this.get(`title`)}
                                    </h3>
                                    <div class="banner__btn-two" data-aos="fade-right" data-aos-delay="600">
                                        <a href="${this.get(`btnLink`)}" class="btn arrow-btn">${this.get(`btnText`)} <img src="${u}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btnText`)}" class="injectable"></a>

                                        <a href="${this.get(`videoLink`)}" class="play-btn popup-video" aria-label="${this.get(`videoBtnText`)}"><i class="fas fa-play"></i> ${this.get(`videoBtnText`)}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-7 col-lg-6 col-md-8">
                                <div class="banner__images-two tg-svg">
                                    <img src="${this.get(`banner_img`)}" class="main-img">
                                    <div class="shape big-shape" data-aos="fade-up" data-aos-delay="600">
                                        <img src="${this.get(`banner_shape_img`)}" alt="shape" class="injectable tg-motion-effects1">
                                    </div>
                                    <span class="svg-icon" id="banner-svg"
                                        data-svg-icon="${u}/frontend/img/banner/banner_shape02.png"></span>
                                    <div class="about__enrolled" data-aos="fade-right" data-aos-delay="200">
                                        <p class="title"><span>${this.get(`count`)}</span> ${this.get(`count_title`)}</p>
                                        <img src="${this.get(`count_img`)}" alt="${this.get(`count_title`)}" />
                                    </div>
                                    <div class="banner__student" data-aos="fade-left" data-aos-delay="200">
                                        <div class="icon">
                                            <img src="${this.get(`count_two_img`)}" alt="img" class="injectable" />
                                        </div>
                                        <div class="content">
                                            <span>${this.get(`count_two_title`)}</span>
                                            <h4 class="title">${this.get(`count_two`)}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <img src="${u}/frontend/img/banner/h2_banner_shape03.svg" alt="shape" class="line-shape-two" data-aos="fade-right" data-aos-delay="1600" />
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{s as default};