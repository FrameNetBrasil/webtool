@php
    $challenge = uniqid(rand());
    session(['challenge', $challenge]);
@endphp
<x-layout.page>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['','Home']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>

        <section id="work" class="w-full h-full">
            <div class="wt-container-center h-full">
                <div id="formLoginDiv">
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
            </div>
        </section>
    </x-slot:main>
</x-layout.page>
