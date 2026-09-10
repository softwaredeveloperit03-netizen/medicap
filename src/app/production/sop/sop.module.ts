import { CommonModule } from '@angular/common';
import { Routes, RouterModule } from '@angular/router';
import { NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { EditorModule } from '@tinymce/tinymce-angular';

import { PdfViewerModule } from 'ng2-pdf-viewer';
import { CheckingComponent } from './checking/checking.component';
import { InitiateLogComponent } from './initiate-log/initiate-log.component';
import { NewComponent } from './new/new.component';
import { DraftComponent } from './draft/draft.component';
import { InitiateCheckingComponent } from './initiate-checking/initiate-checking.component';
import { LogComponent } from './log/log.component';
import { TrainingComponent } from './training/training.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { DistributionComponent } from './distribution/distribution.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InitiationComponent } from './initiation/initiation.component';
import { ReceivingComponent } from './receiving/receiving.component';
import { ChangecontrolComponent } from './changecontrol/changecontrol.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
    { path: '', component: DashboardComponent },
    { path: 'checking', component: CheckingComponent },
    { path: 'changecontrol/:id', component: ChangecontrolComponent },
    { path: 'draft/:id', component: DraftComponent },
    { path: 'initiate', component: InitiationComponent },
    { path: 'initiate-checking', component: InitiateCheckingComponent },
    { path: 'initiate-log', component: InitiateLogComponent },
    { path: 'new', component: NewComponent },
    { path: 'training', component: TrainingComponent },
    { path: 'implementation', component: ImplementationComponent },
    { path: 'distribution', component: DistributionComponent },
    { path: 'log', component: LogComponent },
    { path: 'receive', component: ReceivingComponent },
    { path: 'revision', loadChildren: () => import('./revision/revision.module').then(m=>m.RevisionModule), data: {preload: false}},
];

@NgModule({
  declarations: [
    CheckingComponent,
    InitiateLogComponent,
    NewComponent,
    DraftComponent,
    InitiateCheckingComponent,
    LogComponent,
    TrainingComponent,
    ImplementationComponent,
    DistributionComponent,
    DashboardComponent,
    InitiationComponent,
    ReceivingComponent,
    ChangecontrolComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    FormsModule,
    CommonModule,
    ReactiveFormsModule,
    ClarityModule,
    EditorModule,
    PdfViewerModule,
    RouterModule.forChild(routes)
  ]
})
export class SOPModule { }
