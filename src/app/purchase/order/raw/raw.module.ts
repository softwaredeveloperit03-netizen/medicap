import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { RejectedComponent } from './rejected/rejected.component';
import { IndendComponent } from './indend/indend.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { EditComponent } from './edit/edit.component';
import { StatusComponent } from './status/status.component';
import { StorestatusComponent } from './storestatus/storestatus.component';
import { FolloeupComponent } from './folloeup/folloeup.component';
import { AmmendmentComponent } from './ammendment/ammendment.component';
import { AmendlogComponent } from './amendlog/amendlog.component';
import { RmpmindendComponent } from './rmpmindend/rmpmindend.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'indend', component: IndendComponent },
  { path: 'rmpmindend', component: RmpmindendComponent },
  { path: 'approval', component: ApprovalComponent },
  { path: 'log', component: LogComponent },
  { path: 'rejected', component: RejectedComponent },
  { path: 'status', component: StatusComponent },
  { path: 'followup', component: FolloeupComponent },
  { path: 'storestatus', component: StorestatusComponent },
  { path: 'edit/:id', component: EditComponent },
  { path: 'ammendment', component: AmmendmentComponent },
  { path: 'amendLog', component: AmendlogComponent },
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, LogComponent,
    RejectedComponent, IndendComponent,AmmendmentComponent, EditComponent, FolloeupComponent,StatusComponent, StorestatusComponent, AmendlogComponent, RmpmindendComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule, 
    RouterModule.forChild(routes),
    
  ]
})
export class RawModule { }
