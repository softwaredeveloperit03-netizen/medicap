import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ManagmentComponent } from './managment/managment.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { SamplevendorComponent } from './samplevendor/samplevendor.component';
import { ReportComponent } from './report/report.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'report', component: ReportComponent},
  { path: 'samplevendor', component: SamplevendorComponent},
  { path: 'managment', component: ManagmentComponent}
  
];


@NgModule({
  declarations: [
    ManagmentComponent,
    DashboardComponent,
    SamplevendorComponent,
    ReportComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class SamplevendorModule { }
