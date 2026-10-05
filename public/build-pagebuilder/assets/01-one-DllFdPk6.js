import{t as e}from"./registerComponent-vPl6l5lM.js";import{a as t,i as n,n as r,o as i,r as a,s as o,t as s}from"./brand07-DcXUHnHa.js";import{t as c}from"./brand_star-DIBYIcSK.js";var l=``+new URL(`brand-fi1f1h0d.webp`,import.meta.url).href,u=(u,{folderName:d,fileName:f})=>{e({editor:u,name:f,category:d,media:`<img src="${l}"/>`,model:{defaults:{tagName:`div`,attributes:{class:`brand-area`},traits:[{type:`accordion`,label:`Brands`,name:`brands`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Brand`},{name:`logo`,type:`image-upload`,label:`Logo`,default:o}]}],brands:[{title:`Brand 1`,logo:o},{title:`Brand 2`,logo:i},{title:`Brand 3`,logo:t},{title:`Brand 4`,logo:n},{title:`Brand 5`,logo:a},{title:`Brand 6`,logo:r},{title:`Brand 7`,logo:s}],script:function(){let e=this,t=$(e).find(`.marquee_mode`);t.length&&!t.hasClass(`marquee-initialized`)&&(t.addClass(`marquee-initialized`),t.marquee({speed:20,gap:35,delayBeforeStart:0,direction:`left`,duplicated:!0,pauseOnHover:!0,startVisible:!0}))}},init(){let e=this;e.on(`change:brands`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="container-fluid">
                        <div class="marquee_mode">
                            ${(this.get(`brands`)||[]).map(e=>`
                    <div class="brand__item">
                        <a href="#"><img src="${e.logo}" alt="${e.title}"></a>
                        <img src="${c}" alt="star">
                    </div>
                `).join(``)}
                        </div>
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{u as default};