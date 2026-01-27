@php
    $challenge = uniqid(rand());
    session(['challenge', $challenge]);
@endphp
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['','Home']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-center">
                    @fragment('form')
                        <x-form
                            id="formLogin"
                            title="Login"
                            center="true"
                            hx-post="/login"
                            hx-target="#formLoginDiv"
                        >
                            <x-slot:fields>
                                <div style="text-align: center">
                                    <img src="/images/fnbr_logo.png" />
                                </div>
                                <div class="field">
                                    <x-text-field
                                        id="login"
                                        label="Login"
                                        value=""
                                    ></x-text-field>
                                </div>
                                <div class="field">
                                    <x-password-field
                                        id="password"
                                        label="Password"
                                    ></x-password-field>
                                </div>
                            </x-slot:fields>

                            <x-slot:buttons>
                                <x-submit
                                    label="Login"
                                ></x-submit>
                            </x-slot:buttons>
                        </x-form>
                        <div id="formLoginDiv">
                        </div>
                </div>
                <script>
                    $(function() {
                        $("#formLogin").on("htmx:beforeRequest", event => {
                            let p = event.detail.requestConfig.parameters.password;
                            event.detail.requestConfig.parameters.password = md5(p);
                        });
                    });
                </script>
                @endfragment
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
