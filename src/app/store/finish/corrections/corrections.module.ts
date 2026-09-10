import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DasboardComponent } from './dasboard/dasboard.component';
import { CorrectionComponent } from './correction/correction.component';
import { HistoryComponent } from './history/history.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [
    DasboardComponent,
    CorrectionComponent,
    HistoryComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class CorrectionsModule { }
