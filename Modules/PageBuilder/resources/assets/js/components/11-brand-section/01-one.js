import { registerComponent } from '@utils/registerComponent';
import brand1 from '@images/brand/brand01.png';
import brand2 from '@images/brand/brand02.png';
import brand3 from '@images/brand/brand03.png';
import brand4 from '@images/brand/brand04.png';
import brand5 from '@images/brand/brand05.png';
import brand6 from '@images/brand/brand06.png';
import brand7 from '@images/brand/brand07.png';
import starImg from '@images/icons/brand_star.svg';
import blogManagerImg from '@images/sections/brand.webp';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "div",
                attributes: { class: "brand-area" },
                traits: [
                    {
                        type: 'accordion',
                        label: 'Brands',
                        name: 'brands',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Brand" },
                            { name: 'logo', type: 'image-upload', label: 'Logo', default: brand1 }
                        ]
                    }
                ],
                brands: [
                    { title: 'Brand 1', logo: brand1 },
                    { title: 'Brand 2', logo: brand2 },
                    { title: 'Brand 3', logo: brand3 },
                    { title: 'Brand 4', logo: brand4 },
                    { title: 'Brand 5', logo: brand5 },
                    { title: 'Brand 6', logo: brand6 },
                    { title: 'Brand 7', logo: brand7 },
                ],
                script: function () {
                    const el = this;
                    const $marquee = $(el).find('.marquee_mode');

                    if ($marquee.length && !$marquee.hasClass('marquee-initialized')) {
                        $marquee.addClass('marquee-initialized');

                        $marquee.marquee({
                            speed: 20,
                            gap: 35,
                            delayBeforeStart: 0,
                            direction: "left",
                            duplicated: true,
                            pauseOnHover: true,
                            startVisible: true,
                        });
                    }
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
                    <div class="brand__item">
                        <a href="#"><img src="${brand.logo}" alt="${brand.title}"></a>
                        <img src="${starImg}" alt="star">
                    </div>
                `).join('');

                const html = `
                    <div class="container-fluid">
                        <div class="marquee_mode">
                            ${brandItems}
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
