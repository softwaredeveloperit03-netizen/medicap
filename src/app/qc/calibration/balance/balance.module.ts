import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { MonthlyComponent } from './monthly/monthly.component';
import { DailyComponent } from './daily/daily.component';
import { OocComponent } from './ooc/ooc.component';
 import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormComponent } from './form/form.component';
import { Daily_newComponent } from './daily_new/daily_new.component';
import { NewmonthlyComponent } from './newmonthly/newmonthly.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
    {path:'', component: DashboardComponent},
    {path:'daily', component: DailyComponent},
    {path:'daily_new', component: Daily_newComponent},
    {path:'monthly', component: MonthlyComponent},
    {path:'newmonthly', component: NewmonthlyComponent},
    {path:'ooc', component: OocComponent},
    {path:'form', component: FormComponent},
    
]
@NgModule({
  declarations: [
    DashboardComponent,
    DailyComponent,
    MonthlyComponent,
    OocComponent,
    FormComponent,
    NewmonthlyComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
     FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BalanceModule { }
