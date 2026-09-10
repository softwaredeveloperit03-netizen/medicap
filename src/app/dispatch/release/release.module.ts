import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { UnderTestComponent } from './under-test/under-test.component';
import { QcComponent } from './qc/qc.component';
import { QaComponent } from './qa/qa.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'under-test', component: UnderTestComponent},
  { path: 'qc', component: QcComponent},
  { path: 'qa', component: QaComponent}
];

@NgModule({
  declarations: [DashboardComponent, UnderTestComponent, QcComponent, QaComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ReleaseModule { }
