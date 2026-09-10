import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CriticalComponent } from './critical/critical.component';
import { NoncriticalComponent } from './noncritical/noncritical.component';
import { ReplacementComponent } from './replacement/replacement.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path:'critical',component:CriticalComponent},
  {path:'non-critical',component:NoncriticalComponent},
  {path:'replacement',component:ReplacementComponent},

];


@NgModule({
  declarations: [
    DashboardComponent,
    CriticalComponent,
    NoncriticalComponent,
    ReplacementComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class VentfilterModule { }
