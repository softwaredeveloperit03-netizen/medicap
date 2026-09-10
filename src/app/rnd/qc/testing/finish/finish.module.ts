import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ArComponent } from './ar/ar.component';
import { CoaComponent } from './coa/coa.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [DashboardComponent, NewComponent, ArComponent, CoaComponent],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class FinishModule { }
