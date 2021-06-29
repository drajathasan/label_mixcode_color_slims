/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2021-06-29 10:59:14
 * @modify date 2021-06-29 10:59:14
 * @desc DDC Classification color selector
 */

//  Template
const temp = `
    <div class="mb-32">
        <div class="flex flex-wrap">
            <div class="w-2/12 p-5 font-bold text-gray-800 text-sm">
                Klasifikasi
            </div>
            <div class="w-1/12 p-5 text-center">
                :
            </div>
            <div class="w-9/12 p-5">
                <select v-if="isColorReady" v-on:change="classnumber = $event.target.value" class="w-48 p-2">
                    <option value="pilih">Pilih</option>
                    <option v-for="color in colorList" :value="color">{{ color }}</option>
                </select>
            </div>
        </div>
        <div class="flex flex-wrap">
            <div class="w-2/12 p-5 font-bold text-gray-800 text-sm">
                Klasifikasi Lain
            </div>
            <div class="w-1/12 p-5 text-center">
                :
            </div>
            <div class="w-9/12 p-5">
                <input type="text" class="w-48 p-2 bg-gray-200 inline-block" placeholder="Klasifikasi lain"/>
                <button class="p-2 bg-green-500 rounded-lg text-sm text-white">Tambah Klasifikasi</button>
            </div>
        </div>
        <div class="flex flex-wrap">
            <div class="w-2/12 p-5 font-bold text-gray-800 text-sm">
                Pilih Warna
            </div>
            <div class="w-1/12 p-5 text-center">
                :
            </div>
            <div class="w-9/12 p-5">
                <button ref="colorPicker" class="bg-pink-400 p-2 text-white">Buka Palet</button>
            </div>
        </div>
        <div class="flex flex-wrap">
            <div class="w-2/12 p-5 font-bold text-gray-800 text-sm">
                Hasil
            </div>
            <div class="w-1/12 p-5 text-center">
                :
            </div>
            <div class="w-9/12 p-5 h-48 bg-gray-200 overflow-y-auto" ref="colorResult">
                <div v-for="color,index in colorList" class="w-full p-3" :style="'background-color: '+colorValue[index]" :data-classification="color">
                    <input type="hidden" :ref="'input'+color" :data-classification="color" :value="colorValue[index]"/>
                    {{ color }}
                </div>
            </div>
        </div>
        <div class="w-full p-5">
            <button class="p-2 bg-blue-800 rounded-lg float-right text-sm text-white" v-on:click="saveColor($refs)">Simpan</button>
        </div>
    </div>
`

export default {
    props: {
        colorString: String
    },
    name: 'selectColor',
    template: temp,
    data() {
        return {
            colorList: [],
            colorValue: [],
            isColorReady: true,
            classnumber: ''
        }
    },
    methods: {
        parseColor()
        {
            this.colorList = Object.keys(JSON.parse(this.colorString.replace(/\'/g, '"')))
            this.colorValue = Object.values(JSON.parse(this.colorString.replace(/\'/g, '"')))
            setTimeout(() => {
                this.createColorPicker()
            }, 500);
        },
        createColorPicker()
        {
            let parent = this.$refs.colorPicker
            let colorResult = this.$refs.colorResult.children
            let picker = new Picker(parent)

            // You can do what you want with the chosen color using two callbacks: onChange and onDone.
            picker.onChange = (color) => {
                if (this.classnumber === '' || this.classnumber === 'pilih')
                {
                    top.toastr.error('Anda belum memilih nomor klasifikasi', 'Peringatan')
                    return
                }

                for (let children = 0; children < colorResult.length; children++) {
                    if (colorResult[children].dataset.classification === this.classnumber)
                    {
                        colorResult[children].style.background = color.hex
                        this.$refs['input'+this.classnumber][0].value = color.hex
                    }
                }
            };
        }
    },
    mounted()
    {
        this.parseColor()
    }
}