<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idUser" value="{{$user->idUser}}">

            <div class="three fields">
                <div class="field">
                    <label for="login">Login</label>
                    <div class="ui small input">
                        <input type="text" id="login" name="login" value="{{$user->login}}" readonly>
                    </div>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <div class="ui small input">
                        <input type="text" id="email" name="email" value="{{$user->email}}">
                    </div>
                </div>
                <div class="field">
                    <label for="name">Name</label>
                    <div class="ui small input">
                        <input type="text" id="name" name="name" value="{{$user->name}}">
                    </div>
                </div>
            </div>

            <div class="field">
                <x-combobox.group
                    label="Primary Group"
                    id="idGroup"
                    :value="$user->groups[0]->idGroup ?? 0"
                ></x-combobox.group>
            </div>

            <div class="field">
                <label>Authorization Status</label>
                <div class="ui segment">
                    @if($user->status == '1' || $user->status == 'active')
                        <div class="ui positive message">
                            <i class="check circle icon"></i>
                            User is authorized
                        </div>
                        <button
                            type="button"
                            class="ui negative button"
                            hx-put="/user/{{$user->idUser}}/deauthorize"
                        >
                            Deauthorize User
                        </button>
                    @else
                        <div class="ui warning message">
                            <i class="exclamation circle icon"></i>
                            User is not authorized (status: {{$user->status ?? 'pending'}})
                        </div>
                        <button
                            type="button"
                            class="ui positive button"
                            hx-put="/user/{{$user->idUser}}/authorize"
                        >
                            Authorize User
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/user"
            >
                Save
            </button>
        </div>
    </div>
</form>
