import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { IncidentsRoutingModule } from './incidents-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormComponent } from './form/form.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HttpClientModule } from '@angular/common/http';
import { CheckerComponent } from './checker/checker.component';
import { IncidentLogComponent } from './incident-log/incident-log.component';
import { TrendComponent } from './trend/trend.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [DashboardComponent,
    FormComponent,
      IncidentLogComponent,
      CheckerComponent,
      TrendComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    IncidentsRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule
  ]
})
export class IncidentsModule { }
