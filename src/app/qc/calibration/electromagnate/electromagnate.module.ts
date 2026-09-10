import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { FormComponent } from './form/form.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path:'new', component: NewComponent},
  {path:'checking', component: CheckingComponent},
  {path: 'log', component: LogComponent},
  {path: 'form', component: FormComponent},
  
]
@NgModule({
  declarations: [
    NewComponent,
    CheckingComponent,
    LogComponent,
    DashboardComponent,
    FormComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ElectromagnateModule { }
