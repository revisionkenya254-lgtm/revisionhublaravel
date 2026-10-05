 @foreach (getSelectedInstructors() as $index => $instructor)
     @if ($index < 4)
         <div class="col-xl-3 col-lg-4 col-sm-6">
             <div class="instructor__item-five">
                 <div class="instructor__thumb-five">
                     <a href="{{ route('instructor-details', $instructor->id) }}"><img
                             src="{{ asset($instructor->image) }}" alt="{{ $instructor->name }}"></a>
                     <div class="instructor__social-two">
                         <ul class="list-wrap">
                             @if ($instructor->facebook)
                                 <li><a href="{{ $instructor->facebook }}" aria-label="Facebook"><i
                                             class="fab fa-facebook-f"></i></a></li>
                             @endif
                             @if ($instructor->twitter)
                                 <li><a href="{{ $instructor->twitter }}" aria-label="Twitter"><i
                                             class="fab fa-twitter"></i></a></li>
                             @endif
                             @if ($instructor->linkedin)
                                 <li><a href="{{ $instructor->linkedin }}" aria-label="Linkedin"><i
                                             class="fab fa-linkedin"></i></a></li>
                             @endif
                             @if ($instructor->github)
                                 <li><a href="{{ $instructor->github }}" aria-label="Github"><i
                                             class="fab fa-github"></i></a></li>
                             @endif
                         </ul>
                     </div>
                 </div>
                 <div class="instructor__content-five">
                     <h2 class="title"><a
                             href="{{ route('instructor-details', $instructor->id) }}l">{{ $instructor->name }}</a>
                     </h2>
                     <span>{{ $instructor->job_title }}</span>
                 </div>
             </div>
         </div>
     @endif
 @endforeach
