import { CommonModule } from '@angular/common';
import { Routes, RouterModule } from '@angular/router';
import { NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { EditorModule } from '@tinymce/tinymce-angular';
import { SOPListComponent } from './sop-list/sop-list.component';
import { SopIndexComponent } from './sop-index/sop-index.component';
import { UploadSOPComponent } from './upload-sop/upload-sop.component';
import { ApprovalComponent } from './approval/approval.component';
import { PdfViewerModule } from 'ng2-pdf-viewer';
import { CheckingComponent } from './checking/checking.component';
import { InitiateApprovalComponent } from './initiate-approval/initiate-approval.component';
import { InitiateLogComponent } from './initiate-log/initiate-log.component';
import { NewComponent } from './new/new.component';
import { DraftComponent } from './draft/draft.component';
import { InitiateCheckingComponent } from './initiate-checking/initiate-checking.component';
import { LogComponent } from './log/log.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { DistributionComponent } from './distribution/distribution.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InitiationComponent } from './initiation/initiation.component';
import { ChangecontrolComponent } from './changecontrol/changecontrol.component';
import { ReceivingComponent } from './receiving/receiving.component';
import { DirectComponent } from './direct/direct.component';
import { CKEditorModule } from '@ckeditor/ckeditor5-angular';
import { ObsoleteComponent } from './obsolete/obsolete.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
    { path: '', component: DashboardComponent },
    { path: 'sop-list', component: SOPListComponent },
    { path: 'upload', component: UploadSOPComponent },
    { path: 'sop-index', component: SopIndexComponent },
    { path: 'approval', component: ApprovalComponent },
    { path: 'checking', component: CheckingComponent },
    { path: 'changecontrol/:id', component: ChangecontrolComponent },
    { path: 'draft/:id', component: DraftComponent },
    { path: 'initiate', component: InitiationComponent },
    { path: 'initiate-checking', component: InitiateCheckingComponent },
    { path: 'initiate-approval', component: InitiateApprovalComponent },
    { path: 'initiate-log', component: InitiateLogComponent },
    { path: 'new', component: NewComponent },
    { path: 'implementation', component: ImplementationComponent },
    { path: 'log', component: LogComponent },
    { path: 'receive', component: ReceivingComponent },
    { path: 'direct', component: DirectComponent},
    { path: 'sop-distribution', component: DistributionComponent},
    { path: 'obsolete', component: ObsoleteComponent},
  

    { path: 'revision', loadChildren: () => import('./revision/revision.module').then(m=>m.RevisionModule), data: {preload: false}},
    { path: 'training', loadChildren: () => import('./training/training.module').then(m=>m.TrainingModule), data: {preload: false}},
];

@NgModule({
  declarations: [
    SOPListComponent,
    UploadSOPComponent,
    SopIndexComponent,
    ApprovalComponent,
    CheckingComponent,
    InitiateApprovalComponent,
    InitiateLogComponent,
    NewComponent,
    DraftComponent,
    InitiateCheckingComponent,
    LogComponent,
    ImplementationComponent,
    DistributionComponent,
    DashboardComponent,
    InitiationComponent,
    ChangecontrolComponent,
    ReceivingComponent,
    DirectComponent,
    ObsoleteComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    FormsModule,
    CommonModule,
    ReactiveFormsModule,
    ClarityModule,
    EditorModule,
    PdfViewerModule,
    CKEditorModule,
    RouterModule.forChild(routes)
  ]
})
export class SOPSModule { }
