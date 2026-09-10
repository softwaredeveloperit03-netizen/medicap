import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { KirloskerComponent } from './kirlosker/kirlosker.component';
import { DaikinComponent } from './daikin/daikin.component';
import { BrineComponent } from './brine/brine.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path:'brine',component:BrineComponent},
  { path:'daikin',component:DaikinComponent},
  { path:'kirlosker',component:KirloskerComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    KirloskerComponent,
    DaikinComponent,
    BrineComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EN020Module { }
