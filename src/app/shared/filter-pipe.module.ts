import { NgModule } from '@angular/core';
import { FilterPipe } from '../filter.pipe';

/** Single NgModule for FilterPipe so it is never declared twice on App vs lazy routes. */
@NgModule({
  declarations: [FilterPipe],
  exports: [FilterPipe],
})
export class FilterPipeModule {}
