<div {{$attributes->except('x-model')}}>
    <div class="form-field field" style="overflow:initial">
        <label for="{{$id}}">{{$label}}</label>
        <div id="{{$id}}_dropdown" class="ui tiny selection dropdown" style="overflow:initial">
            <input type="hidden" id="{{$id}}" name="{{$id}}" value="{{$value}}" {{$attributes->only('x-model')}}>
            <i class="dropdown icon"></i>
            <div class="default text"></div>
            <div class="menu">
                @foreach($options as $idOption => $option)
                    <div data-value="{{$idOption}}"
                         class="item p-1 min-h-0"
                    >
                        {{$option}}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
        const dropdown = $('#{{$id}}_dropdown');
        const input = document.getElementById('{{$id}}');

        dropdown.dropdown({
            onChange: function(value, text, $choice) {
                // Update the input value
                if (input) {
                    input.value = value;

                    // If Alpine's x-model is present, update the Alpine component directly
                    if (input.hasAttribute('x-model') && window.Alpine) {
                        const modelName = input.getAttribute('x-model');

                        // Fomantic-UI modals are moved to body, so we need to find the Alpine component by ID
                        // Look for #grapherApp specifically since modals are outside the normal DOM hierarchy
                        const grapherApp = document.getElementById('grapherApp');
                        if (grapherApp && grapherApp._x_dataStack && grapherApp._x_dataStack[0]) {
                            // Update Alpine's reactive data directly
                            grapherApp._x_dataStack[0][modelName] = value;
                        }
                    }

                    // Also dispatch events as fallback
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });

        // If Alpine's x-model is present, watch for programmatic changes from Alpine
        if (input && input.hasAttribute('x-model')) {
            // Create a MutationObserver to watch for Alpine updating the value
            const observer = new MutationObserver(() => {
                const currentValue = input.value;
                const dropdownValue = dropdown.dropdown('get value');
                if (currentValue !== dropdownValue) {
                    dropdown.dropdown('set selected', currentValue);
                }
            });

            observer.observe(input, {
                attributes: true,
                attributeFilter: ['value']
            });
        }
    });
</script>
