## Content Priority Source Ordered Layouts Explained

In 3col region content priority layouts we consider the first region in the
source order to be the most important (highest priority), therefor this is
always the widest region in the visual layout.

For example in the layout "threecol-source-ordered-2-8-2" the "second" region in
the visual layout is the widest, however in the markup it will be the first
region:

Source order:

  <div class="region-first"> ... </div>
  <div class="region-second"> ... </div>
  <div class="region-third"> ... </div>

Visual layout:

   ------------------------------------------------------
  |   second   |            first           |   third    |
   ------------------------------------------------------


Here is another example using the "threecol-source-ordered-2-2-8" layout, it has
the same source order as above, but the visual order changes:

   ------------------------------------------------------
  |   second   |   third    |           first            |
   ------------------------------------------------------


### Why do this?

1. We need a system that is consistent and predictable. We have to choose
   something and the widest region is probably the most likely candidate.

2. In mobile/small screens we want the most important content to bubble to the
   top of each row, because we consider it highest priority. This is
   particularly interesting in narrow layouts using split rows (first region
   becomes full width and the other two wrap below as width defined columns).

For example in a #narrow breakpoint it is very easy to bubble the highest
priority content to the top using a layout like "threecol-split-row-12--6-6":

   ------------------------------------------------------
  |                       first                          |
   ------------------------------------------------------
  |         second          |            third           |
   ------------------------------------------------------

Then in mobile, when there is effectively no layout at all:

   ------------------------------------------------------
  |                       first                          |
   ------------------------------------------------------
  |                       second                         |
   ------------------------------------------------------
  |                       third                          |
   ------------------------------------------------------


NOTE: if you don't want this to happen, i.e. you want the visual order to always
follow the source order, then use the standard layouts.
