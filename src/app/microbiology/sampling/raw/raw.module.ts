import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { RequestComponent } from './request/request.component';
import { NewComponent } from './new/new.component';
import { CheckingComponent } from './checking/checking.component';
import { ApproveComponent } from './approve/approve.component';
import { LogComponent } from './log/log.component';
import { GrnComponent } from './grn/grn.component';
import { AreaComponent } from './area/area.component';
import { LineComponent } from './line/line.component';
import { SampleComponent } from './sample/sample.component';
import { LabelComponent } from './label/label.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path: 'request', component: RequestComponent},
  {path: 'new', component: NewComponent},
  {path: 'checking', component: CheckingComponent},
  {path: 'approval', component: ApproveComponent},
  {path: 'log', component: LogComponent},
  {path: 'grn', component: GrnComponent},
  {path: 'area', component: AreaComponent},
  {path: 'line', component: LineComponent},
  {path: 'sample', component: SampleComponent},
  {path: 'label', component: LabelComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    RequestComponent,
    NewComponent,
    CheckingComponent,
    ApproveComponent,
    LogComponent,
    GrnComponent,
    AreaComponent,
    LineComponent,
    SampleComponent,
    LabelComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class RawModule { }
