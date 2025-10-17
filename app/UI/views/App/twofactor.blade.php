<x-layout.page>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['','Twofactor']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
    <section id="work" class="w-full h-full">
        <div class="wt-container-center h-full">
                <form
                    class="ui form"
                    hx-post="/twofactor"
                >
                    <div class="six fields">
                        <div class="field">
                            <input type="text" name="field0" placeholder="" class="w-2rem p-2" maxlength="1">
                        </div>
                        <div class="field">
                            <input type="text" name="field1" placeholder="" class="w-2rem p-2" maxlength="1">
                        </div>
                        <div class="field">
                            <input type="text" name="field2" placeholder="" class="w-2rem p-2" maxlength="1">
                        </div>
                        <div class="field">
                            <input type="text" name="field3" placeholder="" class="w-2rem p-2" maxlength="1">
                        </div>
                        <div class="field">
                            <input type="text" name="field4" placeholder="" class="w-2rem p-2" maxlength="1">
                        </div>
                        <div class="field">
                            <input type="text" name="field5" placeholder="" class="w-2rem p-2" maxlength="1">
                        </div>
                    </div>
                    <x-submit
                        label="Send"
                    ></x-submit>
                </form>
            </div>
    </section>
        </x-slot:main>
</x-layout.page>
