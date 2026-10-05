import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./placeholder-DfUPplv7.js";var n=``+new URL(`h5_courses_bg-DW4wsisH.webp`,import.meta.url).href,r=``+new URL(`course-seven-DGj966_3.webp`,import.meta.url).href,i=()=>{let e=``;for(let n=0;n<4;n++)e+=`
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="courses__item-six shine__animate-item">
                    <div class="courses__item-thumb-five shine__animate-link">
                        <a href="#"><img
                                src="${t}" alt="Course Title"></a>
                        <a href="javascript:;"
                            class="wsus-wishlist-btn common-white courses__wishlist-two" aria-label="WishList">
                            <i class="far fa-heart"></i>
                        </a>
                    </div>
                    <div class="courses__item-content-five">
                        <ul class="courses__item-meta list-wrap">
                            <li class="courses__review courses__review-two">
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                </div>
                                <span>(5)</span>
                            </li>
                                <li class="price">$99.00</li>
                        </ul>
                        <h2 class="title"><a
                                href="#">Course Title</a>
                        </h2>
                        <p>Artificial Intelligence and Machine</p>
                        <div class="courses__item-content-bottom-two">
                            <ul class="list-wrap">
                                <li>
                                    <div class="icon">
                                        <i class="flaticon-book"></i>
                                    </div>
                                    <p><span>Lessons 21</p>
                                </li>
                                <li>
                                    <div class="icon">
                                        <i class="flaticon-mortarboard"></i>
                                    </div>
                                    <p><span>Students 999</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;return e},a=(e,t,n=!1)=>`
    <div class="tab-pane fade ${n?`show active`:``}" id="${e}-tab-pane" role="tabpanel" aria-labelledby="${e}-tab" tabindex="0">
        <div class="row justify-content-center">
            ${i()}
        </div>
    </div>
`,o=(t,{folderName:i,fileName:o},s)=>{e({editor:t,name:o,category:i,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`courses-area-four courses__bg-three`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],sub_title:`Top Class Courses`,title:`Explore Our World's Best Courses`,description:`Check out the most demanding courses right now`,background_img:n,script:function(){SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;this.swiperInstance=null,e.on(`change:sub_title change:title change:description change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent(),e.bgUpdate(),this.applyRules(this,!0)},updateContent(){let e=`
                    <div class="courses__bg-shape-one">
                        <img src="${s}/frontend/img/courses/h5_courses_bg_shape01.svg" class="injectable">
                    </div>
                    <div class="courses__bg-shape-two">
                        <img src="${s}/frontend/img/courses/h5_courses_bg_shape02.svg" class="injectable">
                    </div>
                    <div class="container home_kindergarten">
                        <div class="row justify-content-center">
                            <div class="col-lg-9 col-xl-8 col-xxl-6">
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get(`sub_title`)}</span>
                                    <h2 class="title bold">${this.get(`title`)}</h2>
                                    <p class="desc">${this.get(`description`)}</p>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-seven -->
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
                                ${a(`all`,`All Courses`,!0)}
                                ${a(`design`,`Design`)}
                                ${a(`business`,`Business`)}
                                ${a(`development`,`Development`)}
                            </div>
                        <!-- DYNAMIC_PART_END -->
                    </div>
                    <div class="courses__shape-wrap-three">
                        <img src="${s}/frontend/img/courses/h5_courses_shape01.svg" alt="shape" data-aos="fade-right"
                            data-aos-delay="400">
                        <img src="${s}/frontend/img/courses/h5_courses_shape02.svg" alt="shape"
                            data-aos="fade-up-right" data-aos-delay="400">
                        <img src="${s}/frontend/img/courses/h5_courses_shape03.svg" alt="shape"
                            class="alltuchtopdown">
                        <img src="${s}/frontend/img/courses/h5_courses_shape04.svg" alt="shape"
                            data-aos="fade-up-left" data-aos-delay="400">
                    </div>
                `;this.components(e),this.applyRules(this,!0)},bgUpdate(){let e=this.get(`background_img`)||``;this.addAttributes({"data-background":e}),this.addStyle({"background-image":`url('${e}')`})},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};