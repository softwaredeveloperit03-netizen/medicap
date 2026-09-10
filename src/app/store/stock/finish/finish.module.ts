import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApprovedComponent } from './approved/approved.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { QuarantineComponent } from './quarantine/quarantine.component';
import { TestComponent } from './test/test.component';
import { RouterModule, Routes } from '@angular/router';
import {DatePipe} from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'quarantine', component: QuarantineComponent},
  { path: 'test', component: TestComponent},
  { path: 'approved', component: ApprovedComponent}
];

@NgModule({
  declarations: [
    ApprovedComponent,
    DashboardComponent,
    QuarantineComponent,
    TestComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule ,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ],
  providers:[DatePipe]
})
export class FinishModule { }
