@extends(backpack_view('blank'))

@section('title', 'My account')

@section('content')
    <h1 class="h2 mb-4">My account</h1>
    <dl class="row">
        <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $parent->full_name }}</dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $parent->email ?: '-' }}</dd>
        <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $parent->phone ?: '-' }}</dd>
    </dl>
    <a href="{{ backpack_url('dashboard') }}"><i class="la la-arrow-left" aria-hidden="true"></i> My children</a>
@endsection
