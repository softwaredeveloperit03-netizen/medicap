import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { PoComponent } from './po/po.component';
import { TodaysComponent } from './todays/todays.component';
import { LogComponent } from './log/log.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { CameraComponent } from './camera/camera.component';
import { WebcamModule } from 'ngx-webcam';
import { ReturnComponent } from './return/return.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';
import { DirectComponent } from './direct/direct.component';
import { NewComponent } from './direct/new/new.component';
import { DirectApprovalComponent } from './direct/approval/approval.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'direct', component: DirectComponent },
  { path: 'return', component: ReturnComponent },
  { path: 'po', component: PoComponent },
  { path: 'todays', component: TodaysComponent },
  { path: 'log', component: LogComponent },
  { path: 'barcodePrinting', component: CameraComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    PoComponent,
    TodaysComponent,
    LogComponent,
    CameraComponent,
    ReturnComponent,
    DirectComponent,
    NewComponent,
    DirectApprovalComponent,
  ],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    WebcamModule,
    MultiSelectModule,
    DropdownModule,
    DocsIconsModule,
    RouterModule.forChild(routes),
  ],
})
export class InwordModule {}
