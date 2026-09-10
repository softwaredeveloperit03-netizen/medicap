import { NgModule } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';

import { NewComponent } from './new/new.component';
import { HomeComponent } from './home/home.component';
import { FormsModule,ReactiveFormsModule} from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { DropdownModule } from 'primeng/dropdown';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { CorrectionComponent } from './correction/correction.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import {MultiSelectModule} from 'primeng/multiselect';
import { FgreportComponent } from './fgreport/fgreport.component';
import { PmreportComponent } from './pmreport/pmreport.component';
 import { EditSpecificationComponent } from './edit-specification/edit-specification.component';
 import { ObsolateComponent } from './obsolate/obsolate.component';
import { SpecificationLogsComponent } from './specification-logs/specification-logs.component';
import { SpecificationRevisionComponent } from './specification-revision/specification-revision.component';
import { SpecificationRevisionLogComponent } from './specification-revision-log/specification-revision-log.component';
import { RevisionViewComponent } from '../revision/revision-view/revision-view.component';
import { ViewSpecificationComponent } from '../revision/view-specification/view-specification.component';
import { RequestComponent } from '../revision/request/request.component';
import { PeriodicComponent } from '../revision/periodic/periodic.component';
import { EditSpecificationComponent as RevisionEditSpecificationComponent } from '../revision/edit-specification/edit-specification.component';
import { DocsIconsModule } from '../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'edit/:id', component: EditSpecificationComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'correction', component: CorrectionComponent},
  { path: 'dashboard', component: DashboardComponent},
  { path: 'log', component: HomeComponent},
  { path: 'rejected', component: ObsolateComponent},
  { path: 'fgreport', component: FgreportComponent},
  { path: 'pm-report', component: PmreportComponent},
  { path: 'specification-logs', component: SpecificationLogsComponent},
  { path: 'specification-revision', component: SpecificationRevisionComponent},
  { path: 'specification-revision/specification-revision', component: SpecificationRevisionLogComponent},
  { path: 'specification-revision/revision-status', component: RequestComponent},
  { path: 'specification-revision/revision-history-log', component: RevisionViewComponent},
  { path: 'specification-revision/periodic-review', component: PeriodicComponent},
  { path: 'specification-revision/view/:id', component: ViewSpecificationComponent},
  { path: 'specification-revision/edit/:id', component: RevisionEditSpecificationComponent},
  { path: 'specification-revision/training', loadChildren: () => import('../revision/training/training.module').then(m=>m.TrainingModule)},
  { path: 'specification-revision/implementation', loadChildren: () => import('../revision/implementation/implementation.module').then(m=>m.ImplementationModule)},
    { path: 'inprocess', loadChildren: () => import('../inprocess/inprocess.module').then(m=>m.InprocessModule), data: {preload: false}},

];

@NgModule({
  declarations: [NewComponent, HomeComponent, CheckingComponent, ApprovalComponent, CorrectionComponent, DashboardComponent, ObsolateComponent, FgreportComponent, EditSpecificationComponent, PmreportComponent, SpecificationLogsComponent, SpecificationRevisionComponent, SpecificationRevisionLogComponent, RevisionViewComponent, ViewSpecificationComponent, RequestComponent, PeriodicComponent, RevisionEditSpecificationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    AutoCompleteModule,
    MultiSelectModule,
    DropdownModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ],
  providers: [DatePipe]
})
export class RawModule { }
