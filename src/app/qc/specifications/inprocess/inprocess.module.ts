import { NgModule } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';

import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HomeComponent } from './home/home.component';
import { NewComponent } from './new/new.component';
import { ReportComponent } from './report/report.component';
import { HistoryComponent } from './history/history.component';
import { ObsolateComponent } from './obsolate/obsolate.component';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { RouterModule, Routes } from '@angular/router';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { DropdownModule } from 'primeng/dropdown';
import { MultiSelectModule } from 'primeng/multiselect';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'new', component: NewComponent},
  { path: 'report', component: ReportComponent},
  { path: 'history', component: HistoryComponent},
  { path: 'obsolate', component: ObsolateComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'revision', loadChildren: () => import('./revision/revision.module').then(m=>m.RevisionModule)},
];

@NgModule({
  declarations: [HomeComponent, NewComponent, ReportComponent, HistoryComponent, ObsolateComponent, CheckingComponent, ApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    AutoCompleteModule,
    DropdownModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ],
  providers: [DatePipe]
})
export class InprocessModule { }
