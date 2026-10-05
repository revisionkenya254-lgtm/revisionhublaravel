import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`instructor-DBthL-Kh.webp`,import.meta.url).href,n=``+new URL(`testimonial-six-C1Bbiq5S.webp`,import.meta.url).href,r=(r,{folderName:i,fileName:a},o)=>{e({editor:r,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`home_kindergarten testimonial__area-four tg-motion-effects`},traits:[{name:`total`,label:`Total Testimonial`,type:`number`,min:1,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],total:4,background_img:`${o}/frontend/img/bg/h4_testimonial_bg.jpg`,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),new Swiper(`.testimonial-active-four`,{slidesPerView:1,spaceBetween:30,observer:!0,observeParents:!0,loop:!0,breakpoints:{1500:{slidesPerView:1},1200:{slidesPerView:1},992:{slidesPerView:1,spaceBetween:24},768:{slidesPerView:1,spaceBetween:24},576:{slidesPerView:1},0:{slidesPerView:1}},pagination:{el:`.testimonial-pagination`,clickable:!0}}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:total change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},generateHTML(e){let n=``;for(let r=0;r<e;r++)n+=`
                    <div class="swiper-slide">
                        <div class="container">
                            <div class="row align-items-center justify-content-center">
                                <div class="col-lg-7 order-0 order-lg-2">
                                    <div class="testimonial__item-four">
                                        <div class="rating">
                                            <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                        </div>
                                        <p>“ I was hesitant about online learning at first, but this platform has completely changed my mind. The courses are well-organized and informative, ”
                                        </p>
                                        <div class="testimonial__bottom-two">
                                            <h4 class="title">Brooklyn Simmons</h4>
                                            <span>Engineer</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-5 col-md-7">
                                    <div class="testimonial__img-two">
                                        <img src="${t}" alt="img">
                                        <div class="shape">
                                            <img src="${o}/frontend/img/others/h5_testimonial_shape01.svg"
                                                alt="shape" class="alltuchtopdown">
                                            <img src="${o}/frontend/img/others/h5_testimonial_shape02.svg"
                                                alt="shape" class="tg-motion-effects4">
                                            <img src="${o}/frontend/img/others/h5_testimonial_shape03.svg"
                                                alt="shape" class="tg-motion-effects3">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;return n},updateContent(){let e=`
                <div class="testimonial__bg-shape-one">
                    <img src="${o}/frontend/img/others/h5_testimonial_bg_shape01.svg" class="injectable">
                </div>
                <div class="testimonial__bg-shape-two">
                    <img src="${o}/frontend/img/others/h5_testimonial_bg_shape02.svg" class="injectable">
                </div>


                <div class="swiper-container testimonial-active-four">
                    <div class="swiper-wrapper">
                        <!-- DYNAMIC_PART_START:testimonial-six total=${this.get(`total`)} -->
                            ${this.generateHTML(parseInt(this.get(`total`)))}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                    <div class="testimonial-pagination testimonial-pagination-two"></div>
                </div>

                <div class="testimonial__shape-wrap">
                    <img src="${o}/frontend/img/others/h5_testimonial_shape04.svg" alt="shape" data-aos="fade-up"
                        data-aos-delay="400">
                    <img src="${o}/frontend/img/others/h5_testimonial_shape05.svg" alt="shape"
                        data-aos="fade-down-left" data-aos-delay="400">
                </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{r as default};