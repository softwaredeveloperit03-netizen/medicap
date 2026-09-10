import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { OperationComponent } from './operation/operation.component';
import { PressureComponent } from './pressure/pressure.component';
import { ReplacementComponent } from './replacement/replacement.component';
import { Scheduleo2Component } from './scheduleo2/scheduleo2.component';
import { Sheduleo1Component } from './sheduleo1/sheduleo1.component';
import { NitrogenlogComponent } from './nitrogenlog/nitrogenlog.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path:'operation',component : OperationComponent},
  {path:'pressure',component : PressureComponent},
  {path:'replcement',component : ReplacementComponent},
  {path:'scheduleO2',component : Scheduleo2Component},
  {path:'sheduleo1',component : Sheduleo1Component},
  {path:'NitrogenLog',component :NitrogenlogComponent }

  
];


@NgModule({
  declarations: [
    DashboardComponent,
    OperationComponent,
    PressureComponent,
    ReplacementComponent,
    Sheduleo1Component,
    Scheduleo2Component,
    NitrogenlogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class NitrogenModule { }
