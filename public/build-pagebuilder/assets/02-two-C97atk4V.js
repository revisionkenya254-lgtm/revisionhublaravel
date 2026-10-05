import{t as e}from"./registerComponent-vPl6l5lM.js";import{a as t,i as n,n as r,o as i,r as a,s as o,t as s}from"./brand07-DcXUHnHa.js";var c=``+new URL(`brand-three-CuBIasu5.webp`,import.meta.url).href,l=(l,{folderName:u,fileName:d})=>{e({editor:l,name:d,category:u,media:`<img src="${c}"/>`,model:{defaults:{tagName:`div`,attributes:{class:`brand-area-three section-pb-120`},traits:[{type:`accordion`,label:`Brands`,name:`brands`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Brand`},{name:`logo`,type:`image-upload`,label:`Logo`,default:o},{name:`link`,type:`text`,label:`Link`,default:`#`}]}],brands:[{title:`Brand 1`,logo:o,link:`#`},{title:`Brand 2`,logo:i,link:`#`},{title:`Brand 3`,logo:t,link:`#`},{title:`Brand 4`,logo:n,link:`#`},{title:`Brand 5`,logo:a,link:`#`},{title:`Brand 6`,logo:r,link:`#`},{title:`Brand 7`,logo:s,link:`#`}],script:function(){new Swiper(`.brand-swiper-active`,{slidesPerView:6,spaceBetween:30,observer:!0,observeParents:!0,loop:!0,breakpoints:{1500:{slidesPerView:6},1200:{slidesPerView:6},992:{slidesPerView:5,spaceBetween:24},768:{slidesPerView:4,spaceBetween:24},576:{slidesPerView:3},0:{slidesPerView:2}}})}},init(){let e=this;e.on(`change:brands`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="swiper-container brand-swiper-active">
                        <div class="swiper-wrapper">
                            ${(this.get(`brands`)||[]).map(e=>`
                    <div class="swiper-slide">
                        <div class="brand__item-two">
                            <a href="${e.link}"><img src="${e.logo}" alt="${e.title}"></a>
                        </div>
                    </div>
                `).join(``)}
                        </div>
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{l as default};