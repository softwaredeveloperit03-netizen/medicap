import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RequesitionComponent } from './requesition/requesition.component';
import { StockRegisterComponent } from './stock-register/stock-register.component';
import { TrainingComponent } from './training/training.component';
import { ProcedureComponent } from './procedure/procedure.component';
import { UsageRecordComponent } from './usage-record/usage-record.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'requisition', component: RequesitionComponent},
  { path: 'stock-register', component: StockRegisterComponent},
  { path: 'training', component: TrainingComponent},
  { path: 'procedure', component: ProcedureComponent},
  { path: 'usage-record', component: UsageRecordComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    RequesitionComponent,
    StockRegisterComponent,
    TrainingComponent,
    ProcedureComponent,
    UsageRecordComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class FirstAidModule { }
