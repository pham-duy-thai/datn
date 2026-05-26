@extends('layouts.admin')

@section('title', $title)

@section('content')
    <section class="admin-page-heading">
        <div>
            <span>{{ $eyebrow }}</span>
            <h1>{{ $title }}</h1>
        </div>
        <a class="admin-primary-link ghost" href="{{ $backUrl }}">Quay lại danh sách</a>
    </section>

    <section class="admin-panel">
        <form class="admin-form-grid" method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            @foreach ($fields as $field)
                @php
                    $value = old($field['name'], $field['value'] ?? '');
                @endphp

                <label @class(['field-wide' => ($field['type'] ?? 'text') === 'textarea'])>
                    <span>{{ $field['label'] }} @if (! empty($field['required']))<em>*</em>@endif</span>

                    @if (($field['type'] ?? 'text') === 'textarea')
                        <textarea
                            name="{{ $field['name'] }}"
                            rows="{{ $field['rows'] ?? 4 }}"
                            @required(! empty($field['required']))
                        >{{ $value }}</textarea>
                    @elseif (($field['type'] ?? 'text') === 'select')
                        <select name="{{ $field['name'] }}" @required(! empty($field['required']))>
                            @if (($field['empty'] ?? null) !== null)
                                <option value="">{{ $field['empty'] }}</option>
                            @endif
                            @foreach (($field['options'] ?? []) as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input
                            type="{{ $field['type'] ?? 'text' }}"
                            name="{{ $field['name'] }}"
                            value="{{ $value }}"
                            @isset($field['min']) min="{{ $field['min'] }}" @endisset
                            @isset($field['max']) max="{{ $field['max'] }}" @endisset
                            @isset($field['step']) step="{{ $field['step'] }}" @endisset
                            @required(! empty($field['required']))
                        >
                    @endif

                    @if (! empty($field['help']))
                        <small>{{ $field['help'] }}</small>
                    @endif
                </label>
            @endforeach

            <div class="admin-form-actions field-wide">
                <button type="submit">{{ $submitLabel }}</button>
                <a href="{{ $backUrl }}">Hủy</a>
            </div>
        </form>
    </section>
@endsection
