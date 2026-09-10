import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
 import { FacordComponent } from './facord/facord.component';
import { NewfacordComponent } from './newfacord/newfacord.component';
import { ProductListComponent } from './product-list/product-list.component';
 import { RegistrationStatusComponent } from './registration-status/registration-status.component';
import { RegistrationQueryComponent } from './registration-query/registration-query.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared/shared.module';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'factory_order', component: FacordComponent},
  { path: 'product_list', component: ProductListComponent},
  { path: 'registration_status', component: RegistrationStatusComponent},
  { path: 'registration_query', component: RegistrationQueryComponent},
 
 
  {path :'clients',loadChildren:()=>import('./clients/clients.module').then(m => m.ClientsModule),data:{preload : false}},
  {path :'leads',loadChildren:()=>import('./leads/leads.module').then(m => m.LeadsModule),data:{preload : false}},
  {path :'quotation',loadChildren:()=>import('./quotation/quotation.module').then(m => m.QuotationModule),data:{preload : false}},
  {path :'po',loadChildren:()=>import('./po/po.module').then(m => m.PoModule),data:{preload : false}},
  {path :'ponew',loadChildren:()=>import('./ponew/ponew.module').then(m => m.PonewModule),data:{preload : false}},
  {path :'complaint',loadChildren:()=>import('./complaint/complaint.module').then(m => m.ComplaintModule),data:{preload : false}},
  {path :'technical',loadChildren:()=>import('./technical/technical.module').then(m => m.TechnicalModule),data:{preload : false}},
  {path :'dossier',loadChildren:()=>import('./dossier/dossier.module').then(m => m.DossierModule),data:{preload : false}},
  {path :'feedback',loadChildren:()=>import('./feedback/feedback.module').then(m => m.FeedbackModule),data:{preload : false}},
  {path :'agent',loadChildren:()=>import('./agent/agent.module').then(m => m.AgentModule),data:{preload : false}},
  {path :'leaddquotation' ,loadChildren:()=>import('./leadquotation/leadquotation.module').then(m=>m.LeadquotationModule),data:{preload : false } },
    {path :'sample',loadChildren:()=>import('./sample/sample.module').then(m => m.SampleModule),data:{preload : false}},
  { path: 'approvalLicences', loadChildren: () => import('./approval-licences/approval-licences.module').then(m=>m.ApprovalLicencesModule), data: {preload: false}},
  { path: 'crmTracking', loadChildren: () => import('./crm-tracking/crm-tracking.module').then(m=>m.CrmTrackingModule), data: {preload: false}},

];

@NgModule({
  declarations: [
    DashboardComponent, FacordComponent, NewfacordComponent, RegistrationStatusComponent,
    ProductListComponent,
  ],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class MarketingModule { }
