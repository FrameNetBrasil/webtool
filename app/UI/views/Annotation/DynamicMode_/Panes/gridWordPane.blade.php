<div
    id="gridWord"
    class="gridWord"
    x-data
>
    <div
        class="grid words w-full"
    >
        <template x-for="word,index in $store.doStore.words">
            <div
                class="col-3"
            >
                <div
                    @click="Alpine.store('doStore').selectWord(index)"
                    :class="'ui card cursor-pointer w-full ' + (word.selected ? 'selected' : '')"
                >
                    <div
                        class="content"
                    >
                        <div
                            class="header"
                        >
                            <div
                                class="word"
                                x-text="word.word"
                            >
                            </div>
                        </div>
                        <div
                            class="description"
                            x-text="word.startTime + '/' + word.endTime"
                        >
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

