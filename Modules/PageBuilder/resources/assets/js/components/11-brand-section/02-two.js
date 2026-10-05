import { registerComponent } from '@utils/registerComponent';
import brand1 from '@images/brand/brand01.png';
import brand2 from '@images/brand/brand02.png';
import brand3 from '@images/brand/brand03.png';
import brand4 from '@images/brand/brand04.png';
import brand5 from '@images/brand/brand05.png';
import brand6 from '@images/brand/brand06.png';
import brand7 from '@images/brand/brand07.png';
import blogManagerImg from '@images/sections/brand-three.webp';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "div",
                attributes: { class: "brand-area-three section-pb-120" },
                traits: [
                    {
                        type: 'accordion',
                        label: 'Brands',
                        name: 'brands',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Brand" },
                            { name: 'logo', type: 'image-upload', label: 'Logo', default: brand1 },
                            { name: 'link', type: 'text', label: 'Link', default: '#' }
                        ]
                    }
                ],
                brands: [
                    { title: 'Brand 1', logo: brand1, link: "#" },
                    { title: 'Brand 2', logo: brand2, link: "#" },
                    { title: 'Brand 3', logo: brand3, link: "#" },
                    { title: 'Brand 4', logo: brand4, link: "#" },
                    { title: 'Brand 5', logo: brand5, link: "#" },
                    { title: 'Brand 6', logo: brand6, link: "#" },
                    { title: 'Brand 7', logo: brand7, link: "#" },
                ],
                script: function () {
                    var brandSwiper = new Swiper('.brand-swiper-active', {
                        // Optional parameters
                        slidesPerView: 6,
                        spaceBetween: 30,
                        observer: true,
                        observeParents: true,
                        loop: true,
                        breakpoints: {
                            '1500': {
                                slidesPerView: 6,
                            },
                            '1200': {
                                slidesPerView: 6,
                            },
                            '992': {
                                slidesPerView: 5,
                                spaceBetween: 24,
                            },
                            '768': {
                                slidesPerView: 4,
                                spaceBetween: 24,
                            },
                            '576': {
                                slidesPerView: 3,
                            },
                            '0': {
                                slidesPerView: 2,
                            },
                        },
                    });
                },
            },
            init() {
                const wrapper = this;
                wrapper.on('change:brands', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                wrapper.updateContent();
            },

            updateContent() {
                const brandItems = (this.get('brands') || []).map(brand => `
                    <div class="swiper-slide">
                        <div class="brand__item-two">
                            <a href="${brand.link}"><img src="${brand.logo}" alt="${brand.title}"></a>
                        </div>
                    </div>
                `).join('');

                const html = `
                <div class="container">
                    <div class="swiper-container brand-swiper-active">
                        <div class="swiper-wrapper">
                            ${brandItems}
                        </div>
                    </div>
                </div>
                `;
                this.components(html);
                this.applyRules(this, true);
            },
            // Recursive function to set editable/draggable/selectable etc.
            applyRules(comp, root = false) {
                comp.set({
                    editable: false,
                    draggable: root,
                    droppable: false,
                    copyable: root,
                    selectable: root,
                });
                comp.components().forEach(child => this.applyRules(child));
            },
        },
    });
};
