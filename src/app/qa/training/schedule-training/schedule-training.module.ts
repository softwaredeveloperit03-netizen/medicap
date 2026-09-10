import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { IdentifiedNeedComponent } from './identified-need/identified-need.component';
import { CategoryComponent } from './category/category.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path:'', component:DashboardComponent},
  {path:'identified-need', component:IdentifiedNeedComponent},
  {path:'category', component:CategoryComponent},
];


@NgModule({
  declarations: [
    DashboardComponent,
    IdentifiedNeedComponent,
    CategoryComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ScheduleTrainingModule { }
