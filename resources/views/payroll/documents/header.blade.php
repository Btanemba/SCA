<table class="letterhead">
    <tr>
        <td class="logo-cell"><img class="logo" src="{{ $academy['logo'] }}" alt="Academy logo"></td>
        <td><div class="academy-name">{{ $academy['name'] }}</div><div class="contact">{!! nl2br(e($academy['address'])) !!}<br>{{ $academy['phone'] }} &middot; {{ $academy['email'] }}@if ($academy['website'])<br>{{ $academy['website'] }}@endif</div></td>
    </tr>
</table>
