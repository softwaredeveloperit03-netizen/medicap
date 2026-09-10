import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DropdownModule } from 'primeng/dropdown';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { HoldComponent } from './hold/hold.component';
import { FrompoComponent } from './frompo/frompo.component';
import { LocalComponent } from './local/local.component';
import { ReturnComponent } from './return/return.component';
import { CorrectionComponent } from './correction/correction.component';
import { DatePipe} from '@angular/common';
import { VerificationComponent } from './verification/verification.component';
import { New_correctionComponent } from './new_correction/new_correction.component';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'frompo', component: FrompoComponent},
  { path: 'local', component: LocalComponent},
  { path: 'return', component: ReturnComponent},
  { path: 'verification', component: VerificationComponent},
  { path: 'new_correction', component: New_correctionComponent},
  { path: 'correction', loadChildren: () => import('./corrections/corrections.module').then(m=>m.CorrectionsModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent, ApprovalComponent, LogComponent,HoldComponent, FrompoComponent, LocalComponent, ReturnComponent, CorrectionComponent, VerificationComponent,New_correctionComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    AutoCompleteModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class ChallanModule { }
