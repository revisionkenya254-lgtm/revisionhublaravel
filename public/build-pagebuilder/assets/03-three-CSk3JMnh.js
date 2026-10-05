import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./placeholder-DfUPplv7.js";var n=``+new URL(`course-three-BUr7E3PM.webp`,import.meta.url).href,r=()=>{let e=``;for(let n=0;n<5;n++)e+=`
            <div class="swiper-slide">
                <div class="courses__item-eight shine__animate-item">
                    <div class="courses__item-thumb-seven shine__animate-link">
                        <a href="#"><img src="${t}" alt="img"></a>
                        <a href="#" class="courses__item-tag-three">Category</a>
                    </div>
                    <div class="courses__item-content-seven">
                        <ul class="courses__item-meta list-wrap">
                        <li class="price">$99.00</li>
                        <li class="courses__wishlist">
                            <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList">
                                <i class="far fa-heart"></i>
                            </a>
                        </li>
                        </ul>
                        <h2 class="title"><a href="#">Course Title</a></h2>
                        <div class="courses__review">
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span>(5 Reviews)</span>
                        </div>
                    </div>
                    <div class="courses__item-bottom-three courses__item-bottom-five">
                        <ul class="list-wrap">
                            <li><i class="flaticon-book"></i>Lessons 21</li>
                            <li><i class="skillgro-group"></i>Students 999</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;return e},i=`
    <div class="courses__nav">
        <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
        <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
    </div>
`,a=(e,t,n=!1)=>`
    <div class="tab-pane fade ${n?`show active`:``}" id="${e}-tab-pane" role="tabpanel" aria-labelledby="${e}-tab" tabindex="0">
        <div class="swiper courses-swiper-active">
            <div class="swiper-wrapper">
                ${r()}
            </div>
        </div>
        ${i}
    </div>
`,o=(t,{folderName:r,fileName:i},o)=>{e({editor:t,name:i,category:r,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`home_business courses-area-six grey-bg-two`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0}],sub_title:`10,000+ unique online courses`,title:`Our Most Popular Courses`,btnText:`See All Courses`,script:function(){new Swiper(`.courses-swiper-active`,{slidesPerView:4,spaceBetween:30,observer:!0,observeParents:!0,loop:!1,breakpoints:{1400:{slidesPerView:4},1200:{slidesPerView:3},992:{slidesPerView:3,spaceBetween:24},768:{slidesPerView:2,spaceBetween:24},576:{slidesPerView:1},0:{slidesPerView:1}},navigation:{nextEl:`.courses-button-next`,prevEl:`.courses-button-prev`}})}},init(){let e=this;this.swiperInstance=null,e.on(`change:sub_title change:title change:btnText`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="section__title-wrap">
                            <div class="row justify-content-center">
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get(`sub_title`)}</span>
                                    <h2 class="title bold">${this.get(`title`)}</h2>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-three -->
                        <div class="row justify-content-center mb-60">
                            <div class="courses__nav">
                                <ul class="nav nav-tabs" id="courseTab" role="tablist">
                                    ${[{id:`all`,label:`All Course`},{id:`design`,label:`Web Design`},{id:`business`,label:`Business`},{id:`development`,label:`Development`}].map((e,t)=>`
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link ${t===0?`active`:``}" id="${e.id}-tab" data-bs-toggle="tab" data-bs-target="#${e.id}-tab-pane"
                                                type="button" role="tab" aria-controls="${e.id}-tab-pane" aria-selected="${t===0}">
                                                ${e.label}
                                            </button>
                                        </li>
                                    `).join(``)}
                                </ul>
                            </div>
                        </div>
                        <div class="tab-content" id="courseTabContent">
                            ${a(`all`,`All Courses`,!0)}
                            ${a(`design`,`Design`)}
                            ${a(`business`,`Business`)}
                            ${a(`development`,`Development`)}
                        </div>
                        <!-- DYNAMIC_PART_END -->
                        <div class="discover-courses-btn text-center mt-30">
                            <a href="{{ route('courses') }}" class="btn arrow-btn">${this.get(`btnText`)} <img src="${o}/frontend/img/icons/right_arrow.svg" alt="" class="injectable"></a>
                        </div>
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};