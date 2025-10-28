@php
    $challenge = uniqid(rand());
    session(['challenge', $challenge]);
@endphp
<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['','Home']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full">
                <div class="page-center">
                    <div id="formLoginDiv">
                        @fragment('form')
                            <form>
                                <div class="ui card form-card w-full p-1">
                                    <div class="content">
                                        <div class="header">
                                            Login
                                        </div>
                                        <div class="description">

                                        </div>
                                    </div>
                                    <div class="content">
                                        <div class="ui form">
                                            <div style="text-align: center">
                                                <img src="/images/fnbr_logo.png" />
                                            </div>
                                            <div class="field">
                                                <label for="login">Login</label>
                                                <div class="ui small input">
                                                    <input type="text" id="login" name="login" value="">
                                                </div>
                                            </div>
                                            <div class="field">
                                                <label for="password">Password</label>
                                                <div class="ui small input">
                                                    <input type="password" id="password" name="password" value="">
                                                </div>
                                            </div>
                                            <div class="extra content">
                                                <div class="ui buttons">
                                                    <button
                                                        class="ui button primary"
                                                        hx-post="/login"
                                                        hx-target="#formLoginDiv"
                                                    >Login
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @endfragment
                    </div>
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
