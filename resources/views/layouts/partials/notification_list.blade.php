@if(!empty($notifications_data))
  @foreach($notifications_data as $notification_data)
    <li class="@if(empty($notification_data['read_at'])) unread @endif notification-li tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-600 tw-transition-all tw-duration-200 tw-rounded-lg hover:tw-text-gray-900 hover:tw-bg-gray-100">
      <div class="tw-flex tw-items-start tw-justify-between tw-gap-2 tw-w-full">
        <a href="{{$notification_data['link'] ?? '#'}}" class="tw-flex-1 tw-min-w-0 @if(isset($notification_data['show_popup'])) show-notification-in-popup @endif">
          <i class="notif-icon {{$notification_data['icon_class'] ?? ''}}"></i>
          <span class="notif-info">{!! $notification_data['msg'] ?? '' !!}</span>
          <span class="time">{{$notification_data['created_at']}}</span>
        </a>
        @if(!empty($notification_data['action_link']))
          <a href="{{$notification_data['action_link']}}" class="btn btn-xs btn-link text-primary @if(!empty($notification_data['action_popup'])) show-notification-in-popup @endif" title="{{$notification_data['action_label']}}">{{$notification_data['action_label']}}</a>
        @endif
        <a href="{{ url('/notifications/delete/' . ($notification_data['id'] ?? '')) }}" class="btn btn-xs btn-link text-danger" title="@lang('lang_v1.delete_notification')">
          <i class="fas fa-times"></i>
        </a>
      </div>
    </li>
  @endforeach
@else
  <li class="text-center no-notification notification-li">
    @lang('lang_v1.no_notifications_found')
  </li>
@endif