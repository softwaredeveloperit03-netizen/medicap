import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InititateComponent } from './inititate/inititate.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AttachmentComponent } from './attachment/attachment.component';
import { CheckingComponent } from './checking/checking.component';
import { PreapprovalComponent } from './preapproval/preapproval.component';
import { PrecheckingComponent } from './prechecking/prechecking.component';
import { ReviewComponent } from './review/review.component';
import { PostapprovalComponent } from './postapproval/postapproval.component';
import { LogComponent } from './log/log.component';
import { ExtensionComponent } from './extension/extension.component';
import { ExtapprovalComponent } from './extapproval/extapproval.component';
import { AdditionalComponent } from './additional/additional.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { ClosingComponent } from './closing/closing.component';
import { Postapproval1Component } from './postapproval1/postapproval1.component';
import { InitiateccComponent } from './initiatecc/initiatecc.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'icc', component: InitiateccComponent},
  { path: 'initiate', component: InititateComponent}, 
  { path: 'attachment', component: AttachmentComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'preapproval', component: PreapprovalComponent},
  { path: 'prechecking', component: PrecheckingComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'postapproval', component: PostapprovalComponent},
  { path: 'postapproval1', component: Postapproval1Component},
  { path: 'log', component: LogComponent},
  { path: 'extension', component:ExtensionComponent},
  { path: 'extapproval', component: ExtapprovalComponent},
  { path: 'additional', component: AdditionalComponent},
  { path: 'implementation', component: ImplementationComponent},
  { path: 'closing', component: ClosingComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    InititateComponent,
    AttachmentComponent,
    CheckingComponent,
    PreapprovalComponent,
    PrecheckingComponent,
    ReviewComponent,
    PostapprovalComponent,
    LogComponent,
    ExtensionComponent,
    ExtapprovalComponent,
    AdditionalComponent,
    ImplementationComponent,
    ClosingComponent,
    Postapproval1Component,
    InitiateccComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CctemporaryModule { }
