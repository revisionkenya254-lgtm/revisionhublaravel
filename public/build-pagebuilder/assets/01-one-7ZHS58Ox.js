import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./testi_author01-DwhyLpNF.js";var n=``+new URL(`testimonial-BIrVU8fa.webp`,import.meta.url).href,r=(e=4)=>{let n=``;for(let r=0;r<e;r++)n+=`
        <div class="swiper-slide">
            <div class="testimonial__item">
                <div class="testimonial__item-top">
                    <div class="testimonial__author">
                        <div class="testimonial__author-thumb">
                            <img src="${t}" alt="img">
                        </div>
                        <div class="testimonial__author-content">
                            <div class="rating">
                                <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                            </div>
                            <h2 class="title">Wade Warren</h2>
                        </div>
                    </div>
                </div>
                <div class="testimonial__content">
                    <p>“ when an unknown printer took alley offerer area type and scrambled to make a type specimen book has ”</p>
                </div>
            </div>
        </div>
        `;return n},i=(t,{folderName:i,fileName:a},o)=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`testimonial__area section-py-120`},traits:[{name:`total`,label:`Total Testimonial`,type:`number`,min:1,changeProp:!0},{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`textarea`,changeProp:!0}],total:4,sub_title:`Our Testimonials`,title:`What Students Think and Say About Us`,script:function(){new Swiper(`.testimonial-swiper-active`,{slidesPerView:3,spaceBetween:30,observer:!0,observeParents:!0,loop:!0,breakpoints:{1500:{slidesPerView:3},1200:{slidesPerView:3},992:{slidesPerView:3,spaceBetween:24},768:{slidesPerView:2,spaceBetween:24},576:{slidesPerView:1},0:{slidesPerView:1}},navigation:{nextEl:`.testimonial-button-next`,prevEl:`.testimonial-button-prev`}})}},init(){let e=this;e.on(`change:total change:sub_title change:title`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-5">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get(`sub_title`)}</span>
                                <h2 class="title">${this.get(`title`)}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="testimonial__item-wrap">
                                <div class="swiper-container testimonial-swiper-active">
                                    <div class="swiper-wrapper">
                                        <!-- DYNAMIC_PART_START:testimonial total=${this.get(`total`)} -->
                                            ${r(parseInt(this.get(`total`)))}
                                        <!-- DYNAMIC_PART_END -->
                                    </div>
                                </div>
                                <div class="testimonial__nav">
                                    <button class="testimonial-button-prev"><i class="flaticon-arrow-right"></i></button>
                                    <button class="testimonial-button-next"><i class="flaticon-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};