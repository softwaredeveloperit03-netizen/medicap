import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { WasteDisposalRecordComponent } from './waste-disposal-record/waste-disposal-record.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: WasteDisposalRecordComponent, data: { view: 'new' } },
  { path: 'log', component: WasteDisposalRecordComponent, data: { view: 'log' } },
];

@NgModule({
  declarations: [DashboardComponent, WasteDisposalRecordComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class WasteDisposalPharmaModule {}
