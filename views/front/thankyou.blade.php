@extends('front/layout')

@section('title', 'Thank you')

@section('content')

<div class="container">
  <!-- HERO SECTION-->
  <section class="py-5">
    <div class="row">
        <h2 class="h5 text-center text-uppercase mb-4">Thank you, your order is on it's way...</h2>
    </div>
  </section>

  <!-- SERVICES-->
  @component('front/services') @endcomponent
</div>

@endsection