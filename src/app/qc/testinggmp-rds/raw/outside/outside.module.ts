import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { SampleComponent } from './sample/sample.component';
import { ReportComponent } from './report/report.component';
import { UploadComponent } from './upload/upload.component';
import { CorrectionComponent } from './correction/correction.component';
import { DocsIconsModule } from '../../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'sample', component: SampleComponent},
  { path: 'checking', component: UploadComponent},
  { path: 'report', component: ReportComponent},
  { path: 'correction', component: CorrectionComponent},

];

@NgModule({
  declarations: [
    DashboardComponent,
    SampleComponent,
    ReportComponent,
    UploadComponent,
    CorrectionComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class OutsideModule { }
