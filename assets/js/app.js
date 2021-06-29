/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2021-06-29 11:02:26
 * @modify date 2021-06-29 11:02:26
 * @desc App.js
 */

// Load components
import Settings from './../../pages/js/subSettings.js?v=310'
import Rightcode from './../../pages/js/rightCode.js?v=310'
import Leftcode from './../../pages/js/leftCode.js?v=310'
import Bothcode from './../../pages/js/bothCode.js?v=310'
import Selectcolor from './../../pages/js/selectColor.js?v=360'

// Create global data,methods,etc
Vue.mixin({
    methods: {
        // Save form
        async save(type, ref)
        {
            let keys = Object.keys(ref)
            let values = Object.values(ref)
            let action = window.location.href.replace('&action=settings', '')

            let result = {}
            values.forEach((prop,index) => {
                if (typeof prop[0] !== 'undefined')
                {
                    result[keys[index]] = prop[0].value
                }
                else if (typeof prop?.value === 'undefined')
                {
                    delete values[index]
                }
                else
                {
                    result[keys[index]] = prop.value
                }
            })
            result['type'] = type

            await fetch(action, {
                method: 'POST',
                accept: 'application/json',
                body: JSON.stringify(result)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status)
                {
                    parent.toastr.success(result.msg, 'Sukses')
                    setTimeout(() => {
                        location.reload()
                    }, 2000);
                }
                else
                {
                    parent.toastr.error(result.msg, 'Error')
                }
            })
        },
        // save color
        async saveColor(ref)
        {
            delete ref.colorPicker
            delete ref.colorResult
            
            let post = {}
            let keys = Object.keys(ref)
            let action = window.location.href.replace('&action=settings', '')

            keys.forEach(input => {
                if (ref[input][0].value !== '')
                {
                    post[ref[input][0].dataset.classification] = ref[input][0].value
                }
                else
                {
                    post[ref[input][0].dataset.classification] = '#ffffff'
                }
            });

            post['type'] = 'color'

            await fetch(action, {
                method: 'POST',
                accept: 'application/json',
                body: JSON.stringify(post)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status)
                {
                    parent.toastr.success(result.msg, 'Sukses')
                    setTimeout(() => {
                        location.reload()
                    }, 2000);
                }
                else
                {
                    parent.toastr.error(result.msg, 'Error')
                }
            })

        }
    }
})

// App instance
const app = new Vue({
    el: '#content',
    data: {
        section: 'settings'
    },
    components: {
        Settings,
        Rightcode,
        Leftcode,
        Bothcode,
        Selectcolor
    }
})