const temp = `
    <div class="flex flex-wrap mt-5 mb-10">
        <div class="w-3/12 bg-gray-200 px-8 pt-8 pb-32 h-screen overflow-y-auto">
            <button class="my-1 p-2 rounded-lg text-sm text-white bg-blue-800" v-on:click="save('rightCode', $refs)">Simpan</button>
            <span class="text-sm block"><b>NB : satuan yang digunakan yaitu "<code>em</code>"</b></span>
            <label class="w-full">Contoh Kode Item</label>
            <input type="text" class="p-2" :value="itemCode" ref="itemCode" v-on:keyup="changeItemCode($event.target.value)"/>
            <label class="w-full">Ukuran Call Number</label>
            <input type="text" class="p-2" v-model="callNumberFontSize" ref="callNumberFontSize"/>
            <label class="w-full">Lebar Kotak</label>
            <input type="number" class="p-2" v-model="widthBox" ref="widthBox"/>
            <label class="w-full mt-2 block">Tinggi Kotak</label>
            <input type="number" class="p-2" v-model="heightBox" ref="heightBox"/>
            <label class="w-full mt-2 block">Lebar Barcode</label>
            <input type="number" class="p-2" v-model="widthBarcode" ref="widthBarcode"/>
            <label class="w-full mt-2 block">Tinggi Barcode</label>
            <input type="number" class="p-2" v-model="heightBarcode" ref="heightBarcode"/>
            <label class="w-full mt-2 block">Margin Atas Barcode</label>
            <input type="number" class="p-2" v-model="topBarcode" ref="topBarcode"/>
            <label class="w-full mt-2 block">Margin Kiri Barcode</label>
            <input type="number" class="p-2" v-model="leftBarcode" ref="leftBarcode"/>
            <button class="my-1 p-2 rounded-lg text-sm text-white bg-blue-800 mt-2" v-on:click="save('rightCode', $refs)">Simpan</button>
        </div>
        <div class="w-9/12 pl-10 bg-gray-100 h-screen">
            <div class="flex flex-wrap">
                <div :style="'width:' +widthBox+ 'em; height: ' +heightBox+ 'em; border: 1px solid black'">
                    <div class="inline-block" :style="'width: '+(widthBox - 5.4 )+'em ;height: ' +heightBox+ 'em; border-right: 1px solid black'">
                        <span class="w-full block text-center text-sm" style="border-bottom: 1px solid black">{{ libraryName }}</span>
                        <span :class="'w-full block text-center text-md mt-8 font-bold '+callNumberFontSize" v-html="callNumberSplit(callNumber)"></span>
                    </div>
                    <div class="inline-block float-right mr-2" style="width: 75px;">
                        <small class="pl-2 pt-1">Judul ...</small>
                        <img ref="barcode" class="inline-block rot270" :style="'width: ' +widthBarcode+ 'em; height: '+heightBarcode+'em; margin-top: '+topBarcode+'em; margin-left: '+leftBarcode+'em; position: absolute;' "/>
                    </div>
                </div>
            </div>
        </div>
    </div>
`

export default {
    template: temp,
    props: {
        libraryName: String,
        measurement: String,
        codeType: String
    },
    data() {
        return {
            itemCode: 'B00006',
            callNumber: '7965.555 919 Har n',
            callNumberFontSize: 'text-sm',
            widthBox: 20,
            heightBox: 10,
            widthBarcode: 8,
            heightBarcode: 4,
            topBarcode: 3.5,
            leftBarcode: -5
        }
    },
    methods: {
        createBarcode()
        {
            JsBarcode(this.$refs.barcode, this.itemCode)
        },
        changeItemCode(value)
        {
            this.itemCode = value
            this.createBarcode()
        },
        callNumberSplit(callNumber)
        {
            let split = callNumber.split(/(?<=\w)\s+(?=[A-Za-z])/g) // took from Heru Subekti idea
            
            return `${split[0]}<br>${split[1]}<br>${split[2]}`
        },
        setData()
        {
            let data = JSON.parse(this.measurement.replace(/\'/g, '"'))
            let keys = Object.keys(data)
            let value = Object.values(data) 

            keys.forEach((prop,index) => {
                this[prop] = value[index]
            })
        }
    },
    mounted()
    {
        this.setData()
        this.createBarcode()
    }
}