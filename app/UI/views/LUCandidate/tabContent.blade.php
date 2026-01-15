<div class="search-container">
    <div class="search-input-group">
        <form
            class="ui form"
            hx-post="/luCandidate/search"
            hx-target="#tableBody-{{ strtolower($origin) }}"
            hx-swap="innerHTML"
            hx-trigger="submit, input changed delay:500ms from:input[name='lu']"
        >
            @csrf
            <input type="hidden" id="origin-{{ strtolower($origin) }}" name="origin" value="{{ $origin }}">
            <input type="hidden" id="sort-{{ strtolower($origin) }}" name="sort" value="">
            <input type="hidden" id="order-{{ strtolower($origin) }}" name="order" value="">
            <div class="fields">
                <div class="field">
                    <div class="ui left icon input w-full">
                        <i class="search icon"></i>
                        <input
                            type="search"
                            name="lu"
                            placeholder="Search LU"
                            autocomplete="off"
                        >
                    </div>
                </div>
                @if(count($creators) > 0)
                    <div class="field">
                        <div
                            class="ui floating dropdown labeled search icon button"
                            x-init="$($el).dropdown({
                        onChange: (value, text, $selectedItem) => {
                            htmx.trigger($($el).closest('form')[0], 'submit');
                        }
                    })"
                        >
                            <input type="hidden" name="email">
                            <i class="user icon"></i>
                            <span class="text">Select User</span>
                            <div class="menu">
                                <div class="item" data-value="">all users</div>
                                @foreach($creators as $creator)
                                    <div class="item" data-value="{{$creator->email}}">{{$creator->email}}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </form>
    </div>
    <div class="search-result-section">
        <div class="search-result-data">
            <table class="ui selectable striped compact table">
                <thead>
                <tr>
                    <th>LU Candidate
                        <i class="sort icon cursor-pointer"
                           @click="handleSort('name'); htmx.trigger(document.querySelector('[data-tab={{ strtolower($origin) }}] form'), 'submit');"></i>
                    </th>
                    <th>Suggested frame
                        <i class="sort icon cursor-pointer"
                           @click="handleSort('frameName'); htmx.trigger(document.querySelector('[data-tab={{ strtolower($origin) }}] form'), 'submit');"></i>
                    </th>
                    <th>Created at
                        <i class="sort icon cursor-pointer"
                           @click="handleSort('createdAt'); htmx.trigger(document.querySelector('[data-tab={{ strtolower($origin) }}] form'), 'submit');"></i>
                    </th>
                    <th>Created by
                        <i class="sort icon cursor-pointer"
                           @click="handleSort('email'); htmx.trigger(document.querySelector('[data-tab={{ strtolower($origin) }}] form'), 'submit');"></i>
                    </th>
                </tr>
                </thead>
                <tbody id="tableBody-{{ strtolower($origin) }}">
                @forelse($data as $lu)
                    <tr
                        class="cursor-pointer"
                        @click.prevent="window.location.assign('/luCandidate/{{$lu['id']}}')"
                    >
                        <td>
                            {!! $lu['name'] !!}
                        </td>
                        <td>
                            {!! $lu['frameName'] !!}
                        </td>
                        <td>
                            {!! $lu['createdAt'] !!}
                        </td>
                        <td>
                            {!! $lu['createdBy'] !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="center aligned">
                            No LU candidates found for {{ $origin }}.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
