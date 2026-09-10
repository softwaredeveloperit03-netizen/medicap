import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InitiateComponent } from './initiate/initiate.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AttachmentComponent } from './attachment/attachment.component';
import { CheckingComponent } from './checking/checking.component';
import { PreapprovalComponent } from './preapproval/preapproval.component';
import { CustomerComponent } from './customer/customer.component';
import { PrecheckingComponent } from './prechecking/prechecking.component';
import { ReviewComponent } from './review/review.component';
import {PostapprovalComponent} from './postapproval/postapproval.component';
import {Postapproval1Component} from './postapproval1/postapproval1.component';
import{AdditionalComponent} from './additional/additional.component';
import{ImplementationComponent} from './implementation/implementation.component';
import {ClosingComponent} from "./closing/closing.component";
import { Closing1Component } from './closing1/closing1.component';
import {LogComponent} from "./log/log.component";
import { OinitiateComponent } from './oinitiate/oinitiate.component';
import { OcheckingComponent } from './ochecking/ochecking.component';
import { OpreapprovalComponent } from './opreapproval/opreapproval.component';
import { OcustomerComponent } from './ocustomer/ocustomer.component';
import { OprecheckingComponent } from './oprechecking/oprechecking.component';
import { Opostapproval1Component } from './opostapproval1/opostapproval1.component';
import { OadditionalComponent } from './oadditional/oadditional.component';
import { ExtensionComponent } from './extension/extension.component';
import { ExtapprovalComponent } from './extapproval/extapproval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'initiate', component: InitiateComponent},
  { path: 'oinitiate', component: OinitiateComponent},
  { path: 'attachment', component: AttachmentComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'Ochecking', component: OcheckingComponent},
  { path: 'preapproval', component: PreapprovalComponent},
  { path: 'opreapproval', component: OpreapprovalComponent},
  { path: 'customer', component: CustomerComponent},
  { path: 'ocustomer', component: OcustomerComponent},
  { path: 'prechecking', component: PrecheckingComponent},
  { path: 'oprechecking', component: OprecheckingComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'postapproval', component: PostapprovalComponent},
  { path: 'postapproval1', component: Postapproval1Component},
  { path: 'opostapproval1', component: Opostapproval1Component},
  { path: 'additional', component: AdditionalComponent},
  { path: 'oadditional', component: OadditionalComponent},
  { path:'postImplementation',component:ImplementationComponent},
  { path:'closing',component:ClosingComponent},
  { path: 'closing1', component: Closing1Component},
  { path: 'log', component: LogComponent},
  { path: 'extension', component: ExtensionComponent},
  { path: 'extapproval', component: ExtapprovalComponent},

];

@NgModule({
  declarations: [
    DashboardComponent,
    InitiateComponent,
    AttachmentComponent,
    CheckingComponent,
    PreapprovalComponent,
    CustomerComponent,
    ReviewComponent,
    PrecheckingComponent,
    PostapprovalComponent,
    Postapproval1Component,
    AdditionalComponent,
    ImplementationComponent,
    ClosingComponent,
    Closing1Component,
    LogComponent,
    OinitiateComponent,
    OcheckingComponent,
    OpreapprovalComponent,
    OcustomerComponent,
    OprecheckingComponent,
    Opostapproval1Component,
    OadditionalComponent,
    ExtensionComponent,
    ExtapprovalComponent,

  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CcpermanantModule { }
