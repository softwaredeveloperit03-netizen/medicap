import { NgModule } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';

import { HomeComponent } from './home/home.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ReportComponent } from './report/report.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'new', component: NewComponent},
  { path: 'report', component: ReportComponent},
  { path: 'revision', loadChildren: () => import('./revision/revision.module').then(m=>m.RevisionModule)},
];

@NgModule({
  declarations: [HomeComponent, NewComponent, ReportComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ],
  providers: [DatePipe]
})
export class RawModule { }
