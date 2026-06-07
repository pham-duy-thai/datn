@extends('layouts.admin')

@section('title', $title)

@section('content')
    <section class="admin-page-heading">
        <div>
            <span>{{ $eyebrow }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
        </div>
        <a class="admin-primary-link" href="{{ $createUrl }}">{{ $createLabel ?? 'Thêm mới' }}</a>
    </section>

    <form class="admin-filter" method="GET" action="{{ route($routeName) }}">
        <label>
            <span>Tìm kiếm</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $searchPlaceholder }}">
        </label>

        @foreach ($filters as $filter)
            <label>
                <span>{{ $filter['label'] }}</span>
                <select name="{{ $filter['name'] }}">
                    <option value="">Tất cả</option>
                    @foreach ($filter['options'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) request($filter['name']) === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endforeach

        <button type="submit">Lọc</button>
        <a href="{{ route($routeName) }}">Xóa</a>
    </form>

    <section class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table wide">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($row['cells'] as $cell)
                                <td><span class="cell-lines">{{ $cell }}</span></td>
                            @endforeach
                            <td>
                                <div class="admin-actions">
                                    <a href="{{ $row['editUrl'] }}">Sửa</a>
                                    @foreach (($row['extraActions'] ?? []) as $action)
                                        <form method="POST" action="{{ $action['url'] }}" onsubmit="return confirm(@js($action['confirm'] ?? 'Bạn chắc chắn muốn thực hiện thao tác này?'))">
                                            @csrf
                                            @method($action['method'] ?? 'POST')
                                            <button type="submit">{{ $action['label'] }}</button>
                                        </form>
                                    @endforeach
                                    <form method="POST" action="{{ $row['deleteUrl'] }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa dữ liệu này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}">Không có dữ liệu phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $paginator->links() }}
        </div>
    </section>
@endsection
