import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`h7_choose_img01-BtS3qNPh.webp`,import.meta.url).href,n=``+new URL(`h7_choose_img02-DfRaIQzg.webp`,import.meta.url).href,r=``+new URL(`h7_choose_img03-DmFZKs29.webp`,import.meta.url).href,i=``+new URL(`about-five-DyDBNvU2.webp`,import.meta.url).href,a=(a,{folderName:o,fileName:s},c)=>{e({editor:a,name:s,category:o,media:`<img src="${i}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`choose__area-four tg-motion-effects section-py-140`},traits:[{name:`subtitle`,label:`Subtitle`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`videoLink`,label:`Video Link`,type:`text`,changeProp:!0},{name:`about_img`,label:`Image`,type:`image-upload`,changeProp:!0},{name:`about_img_two`,label:`Image Two`,type:`image-upload`,changeProp:!0},{name:`about_img_three`,label:`Image Three`,type:`image-upload`,changeProp:!0}],subtitle:`Why Choose Us`,title:`Professional Courses taught by industry leaders`,description:`<p>Discover a vast collection of top-rated courses, covering a wide range of subjects and taught by expert instructors. Whether you're looking to advance your career</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>`,videoLink:`https://www.youtube.com/watch?v=pMzGDBP6Bic`,btnLink:`${c}/about-us`,btnText:`More Info`,about_img:t,about_img_two:n,about_img_three:r,script:function(){e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:subtitle change:title change:description change:btnLink change:btnText change:videoLink change:about_img change:about_img_two change:about_img_three`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6 col-md-10">
                            <div class="choose__img-four">
                                <div class="left__side">
                                    <img src="${this.get(`about_img`)}" alt="${this.get(`subtitle`)}" data-aos="fade-down" data-aos-delay="200">
                                    <img src="${this.get(`about_img_two`)}" alt="${this.get(`subtitle`)}" data-aos="fade-up" data-aos-delay="200">
                                </div>
                                <div class="right__side" data-aos="fade-left" data-aos-delay="400">
                                    <img src="${this.get(`about_img_three`)}" alt="${this.get(`subtitle`)}">
                                    <a href="${this.get(`videoLink`)}" class="popup-video" aria-label="Watch introductory video"><i class="fas fa-play"></i></a>
                                </div>
                                <img src="${c}/frontend/img/others/h7_choose_shape01.svg" alt="shape" class="shape shape-one tg-motion-effects4">
                                <img src="${c}/frontend/img/others/h7_choose_shape02.svg" alt="shape" class="shape shape-two alltuchtopdown">
                                <img src="${c}/frontend/img/others/h7_choose_shape03.svg" alt="shape" class="shape shape-three tg-motion-effects7">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="choose__content-four">
                                <div class="section__title mb-20">
                                    <span class="sub-title">${this.get(`subtitle`)}</span>
                                    <h2 class="title bold">${this.get(`title`)}</h2>
                                </div>
                                <div>${this.get(`description`)}</div>
                                <a href="${this.get(`btnLink`)}" class="btn arrow-btn btn-four">${this.get(`btnText`)} <img src="${c}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btnText`)}" class="injectable"></a>
                            </div>
                        </div>
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{a as default};