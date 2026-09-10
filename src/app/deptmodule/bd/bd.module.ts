import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [
    LogComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class BdModule { }
