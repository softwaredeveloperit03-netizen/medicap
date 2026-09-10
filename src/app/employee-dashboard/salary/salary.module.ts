import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';

import { SalaryComponent } from './salary.component';
import { PayroleComponent } from './payrole/payrole.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { StatementComponent } from './statement/statement.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'payrole', component: PayroleComponent},
  { path: 'statement', component: StatementComponent}
 
];

@NgModule({
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes)
  ],
  declarations: [DashboardComponent,PayroleComponent,StatementComponent]
})
export class SalaryModule { }
