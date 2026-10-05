 @foreach (getSelectedInstructors() as $index => $instructor)
     @if ($index < 4)
         <div class="col-lg-3 col-sm-6">
             <div class="instructor__item-four">
                 <div class="instructor__thumb-four">
                     <a href="{{ route('instructor-details', $instructor->id) }}">
                         <img src="{{ asset($instructor->image) }}" alt="img">
                     </a>
                 </div>
                 <div class="instructor__content-four">
                     <h2 class="title"><a
                             href="{{ route('instructor-details', $instructor->id) }}">{{ $instructor->name }}</a>
                     </h2>
                     <span>{{ $instructor->job_title }}</span>
                 </div>
             </div>
         </div>
     @endif
 @endforeach
