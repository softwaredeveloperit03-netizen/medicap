import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
 
 
import {  FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { DashbaordComponent } from './dashbaord/dashbaord.component';
import { CleaningcheckingComponent } from './cleaningchecking/cleaningchecking.component';
import { UsagecheckingComponent } from './usagechecking/usagechecking.component';
import { TranslateModule } from '@ngx-translate/core';
import { UsagelogComponent } from './usagelog/usagelog.component';
import { CleaninglogComponent } from './cleaninglog/cleaninglog.component';


const routes: Routes = [
  { path: '', component: DashbaordComponent},
  { path: 'Cleaning', component: CleaningcheckingComponent},
  { path: 'Usage', component: UsagecheckingComponent},
  { path: 'UsageLog', component: UsagelogComponent},
  { path: 'CleaningLog', component: CleaninglogComponent},
 
    


];

@NgModule({
  declarations: [
    DashbaordComponent, CleaningcheckingComponent,UsagecheckingComponent, UsagelogComponent, CleaninglogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CheckingsModule { }
