<table class="letterhead">
    <tr>
        <td class="logo-cell"><img class="logo" src="{{ $academy['logo'] }}" alt="Academy logo"></td>
        <td>
            <div class="academy-name">{{ $academy['name'] }}</div>
            <div class="contact">
                @if ($academy['address'])<div><strong>School Address:</strong> {!! nl2br(e($academy['address'])) !!}</div>@endif
                @if ($academy['phone'])<div><strong>Phone:</strong> {{ $academy['phone'] }}</div>@endif
                @if ($academy['email'])<div><strong>Email Address:</strong> {{ $academy['email'] }}</div>@endif
                @if ($academy['website'])<div><strong>Website:</strong> {{ $academy['website'] }}</div>@endif
            </div>
        </td>
    </tr>
</table>
