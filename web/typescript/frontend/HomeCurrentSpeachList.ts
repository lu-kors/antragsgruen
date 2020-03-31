import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class HomeCurrentSpeachList {
    private widget;

    constructor(private $element: JQuery) {
        this.widget = new Vue({
            el: this.$element.find(".currentSpeachList")[0],
            template: `<speech-user-widget v-bind:queue="queue" v-bind:user="user" v-bind:csrf="csrf"></speech-user-widget>`,
            data: {
                queue: $element.data('queue'),
                user: $element.data('user'),
                csrf: $('input[name=_csrf]').val() as string,
            }
        });
    }
}