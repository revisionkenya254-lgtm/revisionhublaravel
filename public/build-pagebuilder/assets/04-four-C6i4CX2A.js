import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./testi_author01-DwhyLpNF.js";var n=``+new URL(`testimonial-four-VeVoRo2B.webp`,import.meta.url).href,r=(e=4)=>{let n=``;for(let r=0;r<e;r++)n+=`
        <div class="swiper-slide">
            <div class="testimonial__item-two testimonial__item-five">
                <div class="testimonial__content-two">
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p>“ when an unknown printer took alley offerer area type and scrambled to make a type specimen book has ”</p>
                </div>
                <div class="testimonial__author testimonial__author-two">
                    <div class="testimonial__author-thumb testimonial__author-thumb-two">
                        <img src="${t}" alt="img">
                    </div>
                    <div class="testimonial__author-content testimonial__author-content-two">
                        <h2 class="title">Wade Warren</h2>
                        <span>Designer</span>
                    </div>
                </div>
            </div>
        </div>
        `;return n},i=(t,{folderName:i,fileName:a},o)=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`testimonial__area-six section-py-140 testimonial__bg-three`},traits:[{name:`total`,label:`Total Testimonial`,type:`number`,min:1,changeProp:!0},{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],total:4,sub_title:`Our Testimonials`,title:`What Students Think and Say About Us`,background_img:`${o}/frontend/img/bg/h8_testimonial_bg.jpg`,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),new Swiper(`.testimonial-active-five`,{slidesPerView:3,spaceBetween:30,observer:!0,observeParents:!0,loop:!0,breakpoints:{1500:{slidesPerView:3},1200:{slidesPerView:3},992:{slidesPerView:3,spaceBetween:24},768:{slidesPerView:2,spaceBetween:24},576:{slidesPerView:1},0:{slidesPerView:1}},navigation:{nextEl:`.testimonial-button-next`,prevEl:`.testimonial-button-prev`}}),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:total change:sub_title change:title change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-xl-5 col-lg-6 col-md-8">
                            <div class="section__title mb-50">
                                <span class="sub-title">${this.get(`sub_title`)}</span>
                                <h2 class="title">${this.get(`title`)}</h2>
                            </div>
                        </div>
                        <div class="col-xl-7 col-lg-6 col-md-4">
                            <div class="testimonial__nav-two">
                                <button class="testimonial-button-prev"><i class="flaticon-arrow-right"></i></button>
                                <button class="testimonial-button-next"><i class="flaticon-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-container testimonial-active-five">
                        <div class="swiper-wrapper">
                            <!-- DYNAMIC_PART_START:testimonial-four total=${this.get(`total`)} -->
                                ${r(parseInt(this.get(`total`)))}
                            <!-- DYNAMIC_PART_END -->
                        </div>
                    </div>
                </div>
                <div class="testimonial__shape-wrap-two">
                    <img src="${o}/frontend/img/others/h8_testimonial_shape01.svg" alt="shape"
                        data-aos="fade-down-left" data-aos-delay="400">
                    <img src="${o}/frontend/img/others/h8_testimonial_shape02.svg" alt="shape" data-aos="fade-up-right"
                        data-aos-delay="400">
                </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};