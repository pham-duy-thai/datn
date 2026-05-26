@extends('layouts.admin')

@section('title', 'Quản lý liên hệ')

@section('content')
    @php
        $labels = [
            'new' => 'Mới',
            'read' => 'Đã đọc',
            'replied' => 'Đã phản hồi',
        ];
    @endphp

    <section class="admin-page-heading">
        <div>
            <span>Liên hệ</span>
            <h1>Quản lý yêu cầu liên hệ</h1>
        </div>
        <a class="admin-primary-link" href="{{ route('admin.contacts.create') }}">Thêm liên hệ</a>
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.contacts.index') }}">
        <label>
            <span>Tìm kiếm</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Tên, thư điện tử, chủ đề, nội dung">
        </label>
        <label>
            <span>Trạng thái</span>
            <select name="status">
                <option value="">Tất cả</option>
                @foreach ($labels as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Lọc</button>
        <a href="{{ route('admin.contacts.index') }}">Xóa</a>
    </form>

    <section class="admin-panel">
        <div class="table-responsive">
            <table class="admin-table wide">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Người gửi</th>
                        <th>Chủ đề</th>
                        <th>Nội dung</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr>
                            <td>{{ $contact->id }}</td>
                            <td>
                                <strong>{{ $contact->name }}</strong>
                                <small>{{ $contact->email }}</small>
                                <small>{{ $contact->phone ?: 'Chưa có số điện thoại' }}</small>
                            </td>
                            <td>{{ $contact->subject ?: 'Không có chủ đề' }}</td>
                            <td>{{ str($contact->message)->limit(110) }}</td>
                            <td>{{ $contact->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <form class="inline-update compact" method="POST" action="{{ route('admin.contacts.status', $contact) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status">
                                        @foreach ($labels as $status => $label)
                                            <option value="{{ $status }}" @selected($contact->status === $status)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit">Lưu</button>
                                </form>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <a href="{{ route('admin.contacts.edit', $contact->id) }}">Sửa</a>
                                    <form method="POST" action="{{ route('admin.contacts.destroy', $contact->id) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa liên hệ này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Không có liên hệ phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $contacts->links() }}
        </div>
    </section>
@endsection
