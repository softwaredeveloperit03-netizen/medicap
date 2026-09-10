import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { UserRegulatoryModule } from './user-regulatory/user-regulatory.module';
import { ClientMasterModule } from './client-master/client-master.module';
import { ProductMasterModule } from './product-master/product-master.module';
import { DossierIndexMasterModule } from './dossier-index-master/dossier-index-master.module';
import { DossierRequestModule } from './dossier-request/dossier-request.module';
import { MasterDocumentsModule } from './master-documents/master-documents.module';
import { DossierTradingModule } from './dossier-trading/dossier-trading.module';
import { SubmissionHistoryModule } from './submission-history/submission-history.module';
import { ApprovalAndLicencesModule } from './approval-and-licences/approval-and-licences.module';
import { ReminderAndRenewalModule } from './reminder-and-renewal/reminder-and-renewal.module';
import { SampleRequestPlantModule } from './sample-request-plant/sample-request-plant.module';
import { DossierComplaintsModule } from './dossier-complaints/dossier-complaints.module';
import { DossierReviewModule } from './dossier-review/dossier-review.module';
import { DossierLogModule } from './dossier-log/dossier-log.module';
import { DossierApprovalModule } from './dossier-approval/dossier-approval.module';
import { TranslateModule } from '@ngx-translate/core';
import { ClientDocumentDeptModule } from '../shared/client-document-dept/client-document-dept.module';
import { ClientDocumentDeptComponent } from '../shared/client-document-dept/client-document-dept.component';

import {RegistrationInitComponent} from './registration-init/registration-init.component'
import {RegistrationQueryComponent} from './registration-query/registration-query.component'
import {MarketingEnquiryComponent} from './marketing-enquiry/marketing-enquiry.component'
import {RegistrationStatusComponent} from './registration-status/registration-status.component'

const routes: Routes = [
  { path: '', component: DashboardComponent,pathMatch:'full'},
  { path: 'registerInit', component: RegistrationInitComponent},
  { path: 'registerquery', component: RegistrationQueryComponent},
  { path: 'marketing_enquiry', component: MarketingEnquiryComponent},
  { path: 'registration_status', component: RegistrationStatusComponent},
  { path: 'client-doc-request', component: ClientDocumentDeptComponent, data: { dept: 'Regulatory', closeRoute: '/regulatory-new' } },





 {path:'userReg',loadChildren: () => import('./user-regulatory/user-regulatory.module').then(m=>m.UserRegulatoryModule), data: {preload: false}},
  {path:'clientMstr',loadChildren: () => import('./client-master/client-master.module').then(m=>m.ClientMasterModule), data: {preload: false}},
  {path:'productMstr',loadChildren: () => import('./product-master/product-master.module').then(m=>m.ProductMasterModule), data: {preload: false}},
  {path:'dossierIndex',loadChildren: () => import('./dossier-index-master/dossier-index-master.module').then(m=>m.DossierIndexMasterModule), data: {preload: false}},
  {path:'dossierReq',loadChildren: () => import('./dossier-request/dossier-request.module').then(m=>m.DossierRequestModule), data: {preload: false}},
  {path:'masterDoc',loadChildren: () => import('./master-documents/master-documents.module').then(m=>m.MasterDocumentsModule), data: {preload: false}},
  {path:'dossierTrad',loadChildren: () => import('./dossier-trading/dossier-trading.module').then(m=>m.DossierTradingModule), data: {preload: false}},
  {path:'submissionHistory',loadChildren: () => import('./submission-history/submission-history.module').then(m=>m.SubmissionHistoryModule), data: {preload: false}},
  {path:'ApprovalsLicence',loadChildren: () => import('./approval-and-licences/approval-and-licences.module').then(m=>m.ApprovalAndLicencesModule), data: {preload: false}},
  {path:'Reminder',loadChildren: () => import('./reminder-and-renewal/reminder-and-renewal.module').then(m=>m.ReminderAndRenewalModule), data: {preload: false}},
  {path:'sampleReq',loadChildren: () => import('./sample-request-plant/sample-request-plant.module').then(m=>m.SampleRequestPlantModule), data: {preload: false}},
  {path:'dossierLog',loadChildren: () => import('./dossier-log/dossier-log.module').then(m=>m.DossierLogModule), data: {preload: false}},
  {path:'dossierCompl',loadChildren: () => import('./dossier-complaints/dossier-complaints.module').then(m=>m.DossierComplaintsModule), data: {preload: false}},
  {path:'dossierReview',loadChildren: () => import('./dossier-review/dossier-review.module').then(m=>m.DossierReviewModule), data: {preload: false}},
  {path:'dossierApproval',loadChildren: () => import('./dossier-approval/dossier-approval.module').then(m=>m.DossierApprovalModule), data: {preload: false}},
  { path: 'approvalLicences', loadChildren: () => import('./approval-licences/approval-licences.module').then(m=>m.ApprovalLicencesModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent,RegistrationInitComponent,RegistrationQueryComponent,MarketingEnquiryComponent,RegistrationStatusComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ClientDocumentDeptModule,
    RouterModule.forChild(routes)
  ]
})
export class RegulatoryNewModule { }
