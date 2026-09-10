import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { Payrole_salaryComponent } from './payrole_salary.component';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ApprovalComponent } from './approval/approval.component';
import { StatementComponent } from './statement/statement.component';
import { OstatementComponent } from './ostatement/ostatement.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'statement', component: StatementComponent},
  { path: 'ostatement', component: OstatementComponent}
 
];
@NgModule({
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes)
  ],
  declarations: [
    DashboardComponent,ApprovalComponent,StatementComponent, OstatementComponent
  
  ]
})
export class Payrole_salaryModule { }
