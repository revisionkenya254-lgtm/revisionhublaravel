import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./student_grp-C3uvedIu.js";var n=``+new URL(`theme_university_about_img-Dk8mIZZG.webp`,import.meta.url).href,r=``+new URL(`about-four-CYV-5VC2.webp`,import.meta.url).href,i=(i,{folderName:a,fileName:o},s)=>{e({editor:i,name:o,category:a,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`about-area-four section-pb-120`},traits:[{name:`subtitle`,label:`Subtitle`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`videoLink`,label:`Video Link`,type:`text`,changeProp:!0},{name:`videoBtnText`,label:`Video Button Text`,type:`text`,changeProp:!0},{name:`count`,label:`Count`,type:`text`,changeProp:!0},{name:`count_title`,label:`Count Title`,type:`text`,changeProp:!0},{name:`count_img`,label:`Count Image`,type:`image-upload`,changeProp:!0},{name:`count_two`,label:`Count Two`,type:`text`,changeProp:!0},{name:`count_two_title`,label:`Count Two Title`,type:`text`,changeProp:!0},{name:`about_img`,label:`About Image`,type:`image-upload`,changeProp:!0}],subtitle:`About Campus history`,title:`We Are One of The Largest, Most <br> Diverse Universities in The USA`,description:`<p>Preparing students to make meaningful contributions to society as leaders in a complex wPreparing students to make meaningful contribution.Preparing students to make contributions to society as leaders in a complex.</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>`,videoLink:`https://www.youtube.com/watch?v=pMzGDBP6Bic`,videoBtnText:`Watch Our <br> Video`,about_img:n,count_img:t,btnLink:`${s}/about-us`,btnText:`Know More`,count:`36K+`,count_title:`Enrolled Students`,count_two:`15`,count_two_title:`year <br> of Institutes`,script:function(){e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:subtitle change:title change:count change:count_title change:count_two change:count_two_title change:description change:btnLink change:btnText change:videoLink change:videoBtnText change:about_img change:count_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-lg-5 col-md-9">
                                <div class="about__images-four">
                                    <img src="${this.get(`about_img`)}"
                                        alt="${this.get(`subtitle`)}">
                                        <div class="about__enrolled" data-aos="fade-up" data-aos-delay="400">
                                            <p class="title"><span>${this.get(`count`)}</span>
                                                ${this.get(`count_title`)}</p>
                                            <img src="${this.get(`count_img`)}" alt="${this.get(`count_title`)}">
                                        </div>
                                        <div class="about__video" data-aos="fade-left" data-aos-delay="400">
                                            <a href="${this.get(`videoLink`)}" class="play-btn popup-video" aria-label="Watch introductory video"><i
                                                    class="fas fa-play"></i> ${this.get(`videoBtnText`)}</a>
                                        </div>
                                    <div class="about__year-wrap">
                                        <h2 class="count">${this.get(`count_two`)}</h2>
                                        <h5 class="title">${this.get(`count_two_title`)}</h5>
                                    </div>
                                    <div class="shape">
                                        <img src="${s}/frontend/img/others/h3_about_shape01.svg" alt="shape"
                                            data-aos="fade-right" data-aos-delay="200" class="alltuchtopdown">
                                        <img src="${s}/frontend/img/others/h3_about_shape02.svg" alt="shape"
                                            data-aos="fade-right" data-aos-delay="200">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="about__content-four">
                                    <div class="section__title mb-15">
                                        <span class="sub-title">${this.get(`subtitle`)}</span>
                                        <h2 class="title">${this.get(`title`)}</h2>
                                    </div>
                                    ${this.get(`description`)}
                                    <a href="${this.get(`btnLink`)}"
                                        class="btn arrow-btn">${this.get(`btnText`)} <img
                                            src="${s}/frontend/img/icons/right_arrow.svg" alt="${this.get(`subtitle`)}"
                                            class="injectable"></a>
                                    <img src="${s}/frontend/img/others/h3_about_shape03.svg" alt="shape" class="shape alltuchtopdown">
                                </div>
                            </div>
                        </div>
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};