<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['','Home']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full">
                <div class="page-center">
                    <div id="formDiv">
                        <x-form
                            id="formImpersonating"
                            title=""
                            center="true"
                            hx-post="/impersonating"
                        >
                            <x-slot:fields>
                                <div style="text-align: center">
                                    <img src="/images/fnbr_logo.png"/>
                                </div>
                                <x-text-field
                                    id="idUser"
                                    label="idUser"
                                    value=""
                                ></x-text-field>
                                <x-password-field
                                    id="password"
                                    label="Password"
                                ></x-password-field>
                            </x-slot:fields>

                            <x-slot:buttons>
                                <x-submit
                                    label="Login"
                                ></x-submit>
                            </x-slot:buttons>
                        </x-form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
