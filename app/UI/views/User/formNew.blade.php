<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['/user','Group/User'],['','New User']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-content">
                    <form class="ui form">
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new User
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="three fields">
                                    <div class="field">
                                        <label for="login">Login</label>
                                        <div class="ui small input">
                                            <input type="text" id="login" name="login" value="">
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="email">Email</label>
                                        <div class="ui small input">
                                            <input type="text" id="email" name="email" value="">
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="name">Name</label>
                                        <div class="ui small input">
                                            <input type="text" id="name" name="name" value="">
                                        </div>
                                    </div>
                                </div>

                                <div class="field">
                                    <x-combobox.group
                                        id="idGroup"
                                        label="Group"
                                        value="0"
                                    >
                                    </x-combobox.group>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/user/new"
                                >
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
