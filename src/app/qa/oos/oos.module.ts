import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OosRoutingModule } from './oos-routing.module';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HttpClientModule } from '@angular/common/http';
import { OosDashboardComponent } from './oos-dashboard/oos-dashboard.component';
import { OosLogComponent } from './oos-log/oos-log.component';
import { OosApprovedComponent } from './oos-approved/oos-approved.component';
import { TrendComponent } from './trend/trend.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [
    OosDashboardComponent,
    OosLogComponent,
    OosApprovedComponent,
    TrendComponent,
    ChecklistComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    OosRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
  ]
})
export class OosModule { }
