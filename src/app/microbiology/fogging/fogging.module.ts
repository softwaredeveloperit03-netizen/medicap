import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TodayComponent } from './today/today.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  {path:'',component:DashboardComponent},
  {path:'today',component:TodayComponent}
]

@NgModule({
  declarations: [
    DashboardComponent,
    TodayComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes),
    ClarityModule
  ]
})
export class FoggingModule { }
