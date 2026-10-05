import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`breadcrumb-Bcg_Ec55.webp`,import.meta.url).href,n=``+new URL(`breadcrumb-DF_HdZLL.webp`,import.meta.url).href,r=(r,{folderName:i,fileName:a},o)=>{e({editor:r,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`div`,attributes:{class:`breadcrumb__area breadcrumb__bg`},traits:[{name:`page_title`,label:`Page Title`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{name:`links`,label:`Links`,type:`accordion`,changeProp:!0,inputsConfig:[{name:`title`,label:`Title`,type:`text`,changeProp:!0,default:`Page Name`},{name:`url`,label:`URL`,type:`text`,changeProp:!0,default:`#`}]}],page_title:`Blogs`,background_img:t,links:[{title:`Home`,url:`/`},{title:`Blogs`,url:`#`}],script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:page_title change:background_img change:links`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=this.get(`links`)||[],t=e.map((t,n)=>{let r=n===e.length-1,i=`<span property="itemListElement" typeof="ListItem"><a href="${t.url&&!r?t.url:`javascript:;`}">${t.title}</a></span>`;return r||(i+=`<span class="breadcrumb-separator"><i class="fas fa-angle-right"></i></span>`),i}).join(``),n=`
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="breadcrumb__content">
                                <h3 class="title">${this.get(`page_title`)}</h3>
                                <nav class="breadcrumb">
                                    ${t}
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="breadcrumb__shape-wrap">
                    <img src="${o}/frontend/img/others/breadcrumb_shape01.svg" alt="img" class="alltuchtopdown">
                    <img src="${o}/frontend/img/others/breadcrumb_shape02.svg" alt="img" data-aos="fade-right"
                        data-aos-delay="300">
                    <img src="${o}/frontend/img/others/breadcrumb_shape03.svg" alt="img" data-aos="fade-up"
                        data-aos-delay="400">
                    <img src="${o}/frontend/img/others/breadcrumb_shape04.svg" alt="img" data-aos="fade-down-left"
                        data-aos-delay="400">
                    <img src="${o}/frontend/img/others/breadcrumb_shape05.svg" alt="img" data-aos="fade-left"
                        data-aos-delay="400">
                </div>
                `;this.components(n),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{r as default};