const temp = `
    <div class="mb-32">
        <div class="flex flex-wrap" v-for="field,index in decodeFields(fields)">
            <div class="w-2/12 p-5 font-bold text-gray-800 text-sm">
                {{ field.label }}
            </div>
            <div class="w-1/12 p-5 text-center">
                :
            </div>
            <div class="w-9/12 p-5">
                <input :ref="field.key" type="text" :data-field="field.label" :value="field.value" :class="'p-2 w-full bg-gray-200 in-'+index"/>
            </div>
        </div>
        <div class="w-full p-5">
            <button class="p-2 bg-blue-800 rounded-lg float-right text-sm text-white" v-on:click="save('settings', $refs)">Simpan</button>
        </div>
    </div>
`

export default {
    props: {
        fields: {
            type: String,
            default: ''
        },
        action: String
    },
    template: temp,
    methods: {
        decodeFields(fields)
        {
            if (fields.length > 0)
            {
                return JSON.parse(fields.replace(/\'/g, '"'))
            }
            else
            {
                return []
            }
        }
    },
    mounted()
    {
    }
}