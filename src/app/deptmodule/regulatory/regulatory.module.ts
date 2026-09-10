import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NgxDocViewerModule } from 'ngx-doc-viewer';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';





const routes: Routes = [
  { path: '', component: DashboardComponent},
   { path: 'approvalLicences', loadChildren: () => import('./approval-licences/approval-licences.module').then(m=>m.ApprovalLicencesModule), data: {preload: false}},

];

 

@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    NgxDocViewerModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class RegulatoryModule { }
