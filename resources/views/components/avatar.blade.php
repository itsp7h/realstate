{{-- An account's avatar: the photo when there is one, the initial when there
     is not.

     Five places draw this — the sidebar footer, the shell account chip, the
     sign-out dialog, the mobile dashboard header and the pushed mobile headers
     — and before this each built its own initial with
     `strtoupper(substr($user->name, 0, 1))`. That meant a photo would have had
     to be added five times, and the fallback could drift in five ways.

     @param \App\Models\User|null $user  defaults to the signed-in account
     @param string $class  the disc class for the context (.shell-avatar,
                           .user-avatar, .pm-avatar, .signout-avatar)
     @param string $tag    the element to render — a <button> inside the mobile
                           header's logout form, a <div> or <span> elsewhere --}}
@props(['user' => null, 'class' => 'shell-avatar', 'tag' => 'span'])
@php
    $account = $user ?? auth()->user();
    $photo   = $account?->photoUrl();
@endphp

<{{ $tag }} {{ $attributes->class([$class, 'has-photo' => (bool) $photo]) }}>
    @if($photo)
        {{-- alt is empty on purpose: the name is already beside every one of
             these, so a screen reader announcing it twice is noise. --}}
        <img class="avatar-photo" src="{{ $photo }}" alt="">
    @else
        {{ $account?->initial() ?? '?' }}
    @endif
</{{ $tag }}>
