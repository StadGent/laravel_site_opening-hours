@extends('layouts.app')

@section('content')
    <div class="container">
        <form class="form-horizontal" role="form" method="POST" action="{{ url('/register/confirm/' . $token) }}">
            {!! csrf_field() !!}

            <input type="hidden" name="token" value="{{ $token }}">

            @if ($errors->has('error'))
                <span class="help-block">
                    <strong>{{ $errors->first('error') }}</strong>
                </span>
            @endif

            <div class="form-group">
                <label class="col-md-4 control-label">&nbsp;</label>
                <h4 class="col-md-8">Kies een paswoord om je registratie te voltooien</h4>
                <label class="col-md-4 control-label">E-mailadres</label>

                <div class="col-md-6">
                    <input type="text" class="form-control" name="email" value="{{ $user->email }}" readonly>
                </div>
            </div>

            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">

                <label class="col-md-4 control-label">Paswoord</label>

                <div class="col-md-6">
                    <input type="password" class="form-control" name="password" autofocus>

                    @if ($errors->has('password'))
                        <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                    @endif
                </div>
            </div>

            <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}" id="confirm">
                <label class="col-md-4 control-label">Bevestig je paswoord</label>
                <div class="col-md-6">
                    <input type="password" class="form-control" name="password_confirmation">

                    @if ($errors->has('password_confirmation'))
                        <span class="help-block">
                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                    </span>
                    @endif
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-6 col-md-offset-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="glyphicon glyphicon-btn glyphicon-check"></i> Bevestig registratie
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.3.0/zxcvbn.js"></script>
@endsection
