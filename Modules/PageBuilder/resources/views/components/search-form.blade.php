@props(['placeholder'=>  __('Search here'). '. . .','buttonText'=> __('Find Courses')])
<form action="{{ route('courses') }}" class="slider__search-form">
    <input type="text" name="search" placeholder="{{$placeholder}}">
    <button type="submit">{{$buttonText}}</button>
</form>
