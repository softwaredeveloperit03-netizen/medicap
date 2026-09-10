import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    ApprovalComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class AjendaModule { }
