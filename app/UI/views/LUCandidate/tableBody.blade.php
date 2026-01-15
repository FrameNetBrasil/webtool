<tbody>
@forelse($data as $lu)
    <tr
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
            No LU candidates found.
        </td>
    </tr>
@endforelse
</tbody>
