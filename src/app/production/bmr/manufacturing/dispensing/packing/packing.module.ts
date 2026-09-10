import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { RequestComponent } from './request/request.component';
import { ReceivingComponent } from './receiving/receiving.component';
import { StatusComponent } from './status/status.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'receiving', component: ReceivingComponent},
  { path: 'status', component: StatusComponent},
  
];

@NgModule({
  declarations: [
    DashboardComponent,
    RequestComponent,
    ReceivingComponent,
    StatusComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PackingModule { }
