Vue.component('tab-devto', {
    props: ['saved', 'errors', 'formdata', 'schema'],
    template: '<section>' + 
                '<div class="metaLarge bg-light-yellow lh-copy ba bw1 br2 dib pt1">'+
                    '<h2 class="mt0">Crosspost to dev.to</h2>'+ 
                    '<p>Here are the rules to crosspost this article to dev.to:</p><ul><li>You always have to <span class="bg-black pa1 white">publish the article on typemill again</span> to trigger changes on dev.to !!</li><li>Activate the checkbox "crosspost" and we will crosspost this article to dev.to</li><li>Deactivate the checkbox "crosspost" again and we will set the article on dev.to to draft.</li><li>Delete or unpublish an article on typemill will not trigger any changes on dev.to, because you probably want to manage that on dev.to independently.</li>'+ 
                '</div>' +
                '<form>' +
                '<component v-for="(field, index) in schema.fields"' +
                    ':key="index"' +
                    ':is="selectComponent(field)"' +
                    ':errors="errors"' +
                    ':name="index"' +
                    'v-model="formdata[index]"' +
                    'v-bind="field">' +
                '</component>' + 
                '<div v-if="formdata.response && formdata.response.user" class="metaLarge">' + 
                  '<p class="ttu f7 pb1 mb0">Your article on dev.to</p>' + 
                  '<div class="ba br2 b--gray pa3 mb3"><a :href="formdata.response.url" class="link dark-gray dim"><h2>{{ formdata.response.title }}</h2></a>' + 
                    '<ul class="list pa0 flex"><li class="mr1" v-for="item in formdata.response.tags">#{{item}}</li></ul>'+
                    '<div><small>{{ formdata.response.user.username }} &middot; {{formdata.response.readable_publish_date}}</small></div>' + 
                    '<div v-if="formdata.response.published_at" class="br-pill ba bw1 ph3 pv2 mb2 mt3 dib black bg-light-green">Live</div>' +
                    '<div v-else class="br-pill ba bw1 ph3 pv2 mb2 mt3 dib black bg-yellow">Draft</div>' +
                  '</div>' + 
                '</div>' +
                '<div v-if="saved" class="metaLarge"><div class="metaSuccess">Saved successfully</div></div>' +
                '<div v-if="errors" class="metaLarge"><div class="metaErrors">Please correct the errors above</div></div>' +
                '<div class="large"><input type="submit" @click.prevent="saveInput" value="save"></input></div>' +
              '</form>' + 
              '</section>',
    methods: {
        selectComponent: function(field)
        {
            return 'component-'+field.type;
        },
        saveInput: function()
        {
            this.$emit('saveform');
        },
    }
})