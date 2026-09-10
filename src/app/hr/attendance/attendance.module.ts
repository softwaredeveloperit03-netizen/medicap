import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AllComponent } from './all/all.component';
import { IndividualComponent } from './individual/individual.component';
import { CorrectionComponent } from './correction/correction.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { BulkUploadComponent } from './bulk-upload/bulk-upload.component';
import * as XLSX from 'xlsx';
import { MisspunchComponent } from './misspunch/misspunch.component';
import { TestAttComponent } from './test-att/test-att.component';
import { MachattComponent } from './machatt/machatt.component';
import { MachattereviewComponent } from './machattereview/machattereview.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'all', component: AllComponent},
  { path: 'individual', component: IndividualComponent},
  { path: 'bulk-upload', component: BulkUploadComponent},
  { path: 'misspunch', component: MisspunchComponent},
  { path: 'testAtt', component: TestAttComponent},
  { path: 'MachattComponent', component: MachattComponent},
  { path: 'machattereview', component: MachattereviewComponent},
];

@NgModule({
  declarations: [DashboardComponent, AllComponent, IndividualComponent, CorrectionComponent, BulkUploadComponent, MisspunchComponent, TestAttComponent, MachattComponent, MachattereviewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AttendanceModule { }
