import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./placeholder-DfUPplv7.js";var n=``+new URL(`course-five-CibBgMPW.webp`,import.meta.url).href,r=()=>{let e=``;for(let n=0;n<4;n++)e+=`
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="courses__item-nine shine__animate-item">
                    <div class="courses__item-thumb-eight shine__animate-link">
                        <a href="#"><img src="${t}" alt="img"></a>
                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist" aria-label="WishList" >
                            <i class="far fa-heart"></i>
                        </a>
                        <a href="#"
                            class="courses__item-tag-three">Category</a>
                    </div>
                    <div class="courses__item-content-eight">
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
                        <h2 class="title"><a
                                href="#">Course Title</a>
                        </h2>
                        <h2 class="price">$99.00</h2>
                    </div>
                    <div class="courses__item-bottom-three courses__item-bottom-six">
                        <ul class="list-wrap">
                            <li><i class="flaticon-book"></i>Lessons 21</li>
                            <li><i class="flaticon-mortarboard"></i>Students 999</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;return e},i=(e,t,n=!1)=>`
    <div class="tab-pane fade ${n?`show active`:``}" id="${e}-tab-pane" role="tabpanel" aria-labelledby="${e}-tab" tabindex="0">
        <div class="row gutter-24 justify-content-center">
            ${r()}
        </div>
    </div>
`,a=(t,{folderName:r,fileName:a},o)=>{e({editor:t,name:a,category:r,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`courses-area-seven section-py-140`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0}],sub_title:`Top Class Courses`,title:`Explore Our World's Best Courses`,btnText:`Discover All Class`},init(){let e=this;this.swiperInstance=null,e.on(`change:sub_title change:title change:btnText`,()=>{e.updateContent()}),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-xxl-8 col-lg-10 col-md-12">
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get(`sub_title`)}</span>
                                    <h2 class="title">${this.get(`title`)}</h2>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-five -->
                            <div class="row justify-content-center mb-50">
                                <div class="courses__nav-two">
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
                                ${i(`all`,`All Courses`,!0)}
                                ${i(`design`,`Design`)}
                                ${i(`business`,`Business`)}
                                ${i(`development`,`Development`)}
                            </div>
                        <!-- DYNAMIC_PART_END -->
                        <div class="discover-courses-btn-two text-center mt-30">
                            <a href="{{ route('courses') }}" class="btn arrow-btn btn-four">${this.get(`btnText`)} <img src="${o}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btnText`)}" class="injectable"></a>
                        </div>
                    </div>
                    <div class="courses__shape-wrap-four">
                        <img src="${o}/frontend/img/others/h8_course_shape01.svg" alt="shape" class="rotateme">
                        <img src="${o}/frontend/img/others/h8_course_shape02.svg" alt="shape" class="rotateme">
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{a as default};